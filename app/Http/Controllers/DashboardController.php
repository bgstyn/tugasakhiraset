<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MaintenanceTicket;
use App\Models\TicketHistory;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with asset statistics and recent activities.
     */
    public function index()
    {
        $counts = Asset::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $stats = ['total' => (int) $counts->sum()];
        foreach (['standby', 'digunakan', 'maintenance', 'rusak', 'fraud', 'write_off', 'pending_fraud_approval', 'pending_write_off_approval'] as $status) {
            $stats[$status] = (int) ($counts[$status] ?? 0);
        }
        $stats['open_tickets'] = MaintenanceTicket::whereIn('status', ['open', 'assigned', 'in_progress', 'waiting_sparepart'])->count();

        $statusLabels = [
            'standby' => 'Siap dipakai', 'digunakan' => 'Digunakan', 'maintenance' => 'Dalam perbaikan',
            'rusak' => 'Rusak', 'fraud' => 'Hilang / investigasi', 'write_off' => 'Write off',
            'pending_fraud_approval' => 'Menunggu investigasi', 'pending_write_off_approval' => 'Menunggu write off',
        ];
        $statusColors = ['#2fb344', '#206bc4', '#f59f00', '#d63939', '#ae3ec9', '#667382', '#8b5cf6', '#a16207'];
        $statusChart = collect($statusLabels)->map(fn ($label, $status) => [
            'key' => $status, 'label' => $label, 'value' => $stats[$status],
            'color' => $statusColors[array_search($status, array_keys($statusLabels))],
        ])->values();

        // Fetch room and floor statistics directly from assets table
        $roomStats = Asset::selectRaw('building, room as nama_ruangan, floor as lantai, count(*) as assets_count')
            ->whereNotNull('room')
            ->where('room', '!=', '')
            ->groupBy('building', 'room', 'floor')
            ->orderByDesc('assets_count')
            ->orderBy('building')
            ->orderBy('floor')
            ->orderBy('room')
            ->get();

        $floorStats = Asset::selectRaw('floor as lantai, count(*) as assets_count')
            ->whereNotNull('floor')
            ->groupBy('floor')
            ->orderBy('floor')
            ->get();

        // Fetch the 5 most recent activities
        $recentHistories = AssetHistory::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $rfidStats = [
            'total_assets' => $stats['total'],
            'with_rfid' => Asset::whereNotNull('rfid_uid')->where('rfid_uid', '!=', '')->count(),
            'without_rfid' => Asset::where(fn ($q) => $q->whereNull('rfid_uid')->orWhere('rfid_uid', ''))->count(),
            'rfid_aktif' => Asset::where('rfid_status', 'aktif')->count(),
            'rfid_belum_terdaftar' => Asset::where('rfid_status', 'belum_terdaftar')->count(),
        ];

        // Group in PHP to keep the chart portable across MySQL and SQLite.
        $firstMonth = now()->startOfMonth()->subMonths(5);
        $lastMonth = now()->endOfMonth();
        $opened = MaintenanceTicket::whereBetween('created_at', [$firstMonth, $lastMonth])->get(['id', 'created_at'])->groupBy(fn ($ticket) => $ticket->created_at->format('Y-m'));
        $completed = TicketHistory::where('new_status', 'completed')->whereBetween('created_at', [$firstMonth, $lastMonth])->get(['ticket_id', 'created_at'])->groupBy(fn ($history) => $history->created_at->format('Y-m'));
        $ticketTrend = collect(range(0, 5))->map(function ($offset) use ($firstMonth, $opened, $completed) {
            $month = $firstMonth->copy()->addMonths($offset);
            $key = $month->format('Y-m');
            return [
                'month' => $key, 'label' => $month->locale('id')->translatedFormat('M'),
                'full_label' => $month->locale('id')->translatedFormat('F Y'),
                'opened' => ($opened[$key] ?? collect())->count(),
                'completed' => ($completed[$key] ?? collect())->unique('ticket_id')->count(),
            ];
        });

        return view('dashboard', compact('stats', 'recentHistories', 'roomStats', 'floorStats', 'rfidStats', 'statusChart', 'ticketTrend'));
    }
}

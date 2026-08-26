<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with asset statistics and recent activities.
     */
    public function index()
    {
        $stats = [
            'total' => Asset::count(),
            'standby' => Asset::where('status', 'standby')->count(),
            'digunakan' => Asset::where('status', 'digunakan')->count(),
            'maintenance' => Asset::where('status', 'maintenance')->count(),
            'rusak' => Asset::where('status', 'rusak')->count(),
            'fraud' => Asset::where('status', 'fraud')->count(),
            'write_off' => Asset::where('status', 'write_off')->count(),
        ];

        // Fetch room and floor statistics directly from assets table
        $roomStats = Asset::selectRaw('room as kode_ruangan, room as nama_ruangan, floor as lantai, count(*) as assets_count')
            ->whereNotNull('room')
            ->groupBy('room', 'floor')
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
            'total_assets' => Asset::count(),
            'with_rfid' => Asset::whereNotNull('rfid_uid')->count(),
            'without_rfid' => Asset::whereNull('rfid_uid')->count(),
            'rfid_aktif' => Asset::where('rfid_status', 'aktif')->count(),
            'rfid_belum_terdaftar' => Asset::where('rfid_status', 'belum_terdaftar')->count(),
        ];

        return view('dashboard', compact('stats', 'recentHistories', 'roomStats', 'floorStats', 'rfidStats'));
    }
}

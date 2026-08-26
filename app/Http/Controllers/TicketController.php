<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketController extends Controller
{
    /**
     * Show report damage form for an asset (public).
     */
    public function createPublic(Asset $asset)
    {
        return view('tickets.create', compact('asset'));
    }

    /**
     * Store reported damage ticket (public).
     */
    public function storePublic(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'reporter_name' => 'required|string|max:150',
            'reporter_contact' => 'nullable|string|max:100',
            'description' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/tickets'), $fileName);
            $photoPath = 'uploads/tickets/' . $fileName;
        }

        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id,
            'reporter_name' => $validated['reporter_name'],
            'reporter_contact' => $validated['reporter_contact'],
            'description' => $validated['description'],
            'photo' => $photoPath,
            'priority' => $validated['priority'],
            'status' => 'open',
        ]);

        // Record initial status in history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => null,
            'new_status' => 'open',
            'comment' => 'Tiket laporan kerusakan dibuat oleh pelapor.',
        ]);

        return redirect()->route('tickets.public.success', $ticket->ticket_number);
    }

    /**
     * Public success page.
     */
    public function successPublic($ticket_number)
    {
        $ticket = MaintenanceTicket::where('ticket_number', $ticket_number)->firstOrFail();
        return view('tickets.success', compact('ticket'));
    }

    /**
     * Show staff tickets dashboard.
     */
    public function index(Request $request)
    {
        $query = MaintenanceTicket::with(['asset', 'assignedTechnician']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                  ->orWhere('reporter_name', 'like', "%{$search}%")
                  ->orWhereHas('asset', function($aq) use ($search) {
                      $aq->where('name', 'like', "%{$search}%")
                        ->orWhere('asset_id', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }

        $tickets = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Display a specific ticket with log history and comments.
     */
    public function show(MaintenanceTicket $ticket)
    {
        $ticket->load(['asset', 'assignedTechnician', 'histories.user', 'comments.user']);
        $technicians = User::where('role', 'teknisi')->get();

        return view('tickets.show', compact('ticket', 'technicians'));
    }

    /**
     * Technician claims an open ticket (concurrency-safe lock).
     */
    public function claim(MaintenanceTicket $ticket)
    {
        $user = auth()->user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            return back()->with('error', 'Hanya teknisi yang diperbolehkan mengklaim tiket.');
        }

        // Concurrency-safe atomic check
        $updated = DB::table('maintenance_tickets')
            ->where('id', $ticket->id)
            ->whereNull('assigned_to')
            ->where('status', 'open')
            ->update([
                'assigned_to' => $user->id,
                'status' => 'assigned',
                'updated_at' => now(),
            ]);

        if (!$updated) {
            return back()->with('error', 'Gagal klaim: Tiket ini sudah diambil atau ditugaskan ke teknisi lain.');
        }

        // Log history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'changed_by' => $user->id,
            'old_status' => 'open',
            'new_status' => 'assigned',
            'comment' => 'Tiket diklaim oleh teknisi ' . $user->name . '.',
        ]);

        return back()->with('success', 'Anda berhasil mengambil tugas tiket ini!');
    }

    /**
     * Administrator assigns a ticket to a technician.
     */
    public function assign(Request $request, MaintenanceTicket $ticket)
    {
        if (!auth()->user()->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang dapat menugaskan tiket.');
        }

        $validated = $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        $tech = User::findOrFail($validated['assigned_to']);
        if (!$tech->isTeknisi()) {
            return back()->with('error', 'User terpilih bukan merupakan seorang teknisi.');
        }

        $oldStatus = $ticket->status;
        $oldAssigned = $ticket->assigned_to;

        $ticket->assigned_to = $tech->id;
        if ($ticket->status === 'open') {
            $ticket->status = 'assigned';
        }
        $ticket->save();

        // Log history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'changed_by' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $ticket->status,
            'comment' => 'Tiket ditugaskan ke teknisi ' . $tech->name . ' oleh Administrator.',
        ]);

        return back()->with('success', 'Tiket berhasil ditugaskan ke ' . $tech->name . '!');
    }

    /**
     * Update the ticket's progress status.
     */
    public function updateStatus(Request $request, MaintenanceTicket $ticket)
    {
        $user = auth()->user();
        
        // Authorize: Admin or Assigned Tech
        if (!$user->isAdmin() && $ticket->assigned_to !== $user->id) {
            return back()->with('error', 'Anda tidak memiliki wewenang untuk mengubah status tiket ini.');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,assigned,in_progress,waiting_sparepart,completed,cancelled',
            'comment' => 'nullable|string',
        ]);

        $oldStatus = $ticket->status;
        $newStatus = $validated['status'];

        if ($newStatus === 'completed') {
            $logbookValidated = $request->validate([
                'diagnosis' => 'required|string',
                'cause' => 'required|string',
                'action_taken' => 'required|string',
                'spareparts' => 'nullable|string',
                'photo_before' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'photo_after' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
                'maintenance_date' => 'required|date',
            ]);

            $photoBeforePath = null;
            if ($request->hasFile('photo_before')) {
                $file = $request->file('photo_before');
                $fileName = time() . '_before_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/maintenance'), $fileName);
                $photoBeforePath = 'uploads/maintenance/' . $fileName;
            }

            $photoAfterPath = null;
            if ($request->hasFile('photo_after')) {
                $file = $request->file('photo_after');
                $fileName = time() . '_after_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('uploads/maintenance'), $fileName);
                $photoAfterPath = 'uploads/maintenance/' . $fileName;
            }

            \App\Models\MaintenanceLog::create([
                'asset_id' => $ticket->asset_id,
                'ticket_id' => $ticket->id,
                'technician_id' => $user->id,
                'diagnosis' => $logbookValidated['diagnosis'],
                'cause' => $logbookValidated['cause'],
                'action_taken' => $logbookValidated['action_taken'],
                'spareparts' => $logbookValidated['spareparts'] ?? null,
                'photo_before' => $photoBeforePath,
                'photo_after' => $photoAfterPath,
                'notes' => $validated['comment'] ?? null,
                'maintenance_date' => $logbookValidated['maintenance_date'],
            ]);

            $asset = $ticket->asset;
            if ($asset) {
                $asset->status = 'standby';
                $asset->save();
            }
        }

        $ticket->status = $newStatus;
        $ticket->save();

        // Log history
        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'changed_by' => $user->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'comment' => $validated['comment'] ?: 'Status diubah menjadi ' . $newStatus . '.',
        ]);

        return back()->with('success', 'Status tiket berhasil diperbarui!');
    }

    /**
     * Add comment to a ticket.
     */
    public function storeComment(Request $request, MaintenanceTicket $ticket)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'content' => $validated['content'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan.');
    }

    /**
     * Log maintenance manually for an asset (without a ticket).
     */
    public function storeMaintenanceLog(Request $request, Asset $asset)
    {
        $user = auth()->user();
        if (!$user->isTeknisi() && !$user->isAdmin()) {
            return back()->with('error', 'Hanya teknisi yang diperbolehkan mengisi logbook maintenance.');
        }

        $validated = $request->validate([
            'diagnosis' => 'required|string',
            'cause' => 'required|string',
            'action_taken' => 'required|string',
            'spareparts' => 'nullable|string',
            'photo_before' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'photo_after' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'notes' => 'nullable|string',
            'maintenance_date' => 'required|date',
            'change_status_to_standby' => 'nullable',
        ]);

        $photoBeforePath = null;
        if ($request->hasFile('photo_before')) {
            $file = $request->file('photo_before');
            $fileName = time() . '_before_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/maintenance'), $fileName);
            $photoBeforePath = 'uploads/maintenance/' . $fileName;
        }

        $photoAfterPath = null;
        if ($request->hasFile('photo_after')) {
            $file = $request->file('photo_after');
            $fileName = time() . '_after_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/maintenance'), $fileName);
            $photoAfterPath = 'uploads/maintenance/' . $fileName;
        }

        \App\Models\MaintenanceLog::create([
            'asset_id' => $asset->id,
            'ticket_id' => null,
            'technician_id' => $user->id,
            'diagnosis' => $validated['diagnosis'],
            'cause' => $validated['cause'],
            'action_taken' => $validated['action_taken'],
            'spareparts' => $validated['spareparts'] ?? null,
            'photo_before' => $photoBeforePath,
            'photo_after' => $photoAfterPath,
            'notes' => $validated['notes'] ?? null,
            'maintenance_date' => $validated['maintenance_date'],
        ]);

        if ($request->has('change_status_to_standby')) {
            $asset->status = 'standby';
            $asset->save();
        }

        return back()->with('success', 'Logbook maintenance berhasil dicatat.');
    }
}

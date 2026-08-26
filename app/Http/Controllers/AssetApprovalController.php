<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetApproval;
use App\Models\AssetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetApprovalController extends Controller
{
    /**
     * Admin: Display list of pending/all approval requests.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'pending');

        $query = AssetApproval::with('asset')->orderBy('created_at', 'desc');
        $repQuery = \App\Models\ReplacementRequest::with(['asset', 'requester'])->orderBy('created_at', 'desc');

        if ($filter === 'pending') {
            $query->where('status', 'pending');
            $repQuery->where('status', 'pending');
        }

        $approvals = $query->paginate(20, ['*'], 'approvals_page')->withQueryString();
        $replacements = $repQuery->paginate(20, ['*'], 'replacements_page')->withQueryString();
        
        $pendingApprovalsCount = AssetApproval::where('status', 'pending')->count();
        $pendingReplacementsCount = \App\Models\ReplacementRequest::where('status', 'pending')->count();
        $pendingCount = $pendingApprovalsCount + $pendingReplacementsCount;

        return view('admin.approvals.index', compact('approvals', 'replacements', 'filter', 'pendingCount', 'pendingApprovalsCount', 'pendingReplacementsCount'));
    }

    /**
     * Teknisi: Submit a new approval request for fraud or write_off.
     * Called from the asset show page modal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_id'  => 'required|exists:assets,id',
            'type'      => 'required|in:fraud,write_off',
            'reason'    => 'required|string|min:10|max:1000',
            'notes'     => 'nullable|string|max:500',
            'document'  => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $asset = Asset::findOrFail($validated['asset_id']);
        $staff = session('staff_it');

        // Block if asset already has a pending approval
        if (in_array($asset->status, ['pending_fraud_approval', 'pending_write_off_approval'])) {
            return back()->with('error', 'Aset ini sudah memiliki pengajuan yang sedang menunggu persetujuan.');
        }

        // Handle file upload
        $documentPath = null;
        if ($request->hasFile('document')) {
            $documentPath = $request->file('document')->store('approvals', 'public');
        }

        $previousStatus = $asset->status;
        $pendingStatus  = $validated['type'] === 'fraud'
            ? 'pending_fraud_approval'
            : 'pending_write_off_approval';

        $approval = AssetApproval::create([
            'asset_id'             => $asset->id,
            'asset_name'           => $asset->name,
            'type'                 => $validated['type'],
            'previous_status'      => $previousStatus,
            'reason'               => $validated['reason'],
            'document_path'        => $documentPath,
            'notes'                => $validated['notes'] ?? null,
            'requested_by_name'    => $staff['name'],
            'requested_by_position'=> $staff['position'],
            'status'               => 'pending',
        ]);

        $asset->status = $pendingStatus;
        $asset->save();

        AssetHistory::create([
            'asset_id'            => $asset->id,
            'asset_name'          => $asset->name,
            'action'              => 'approval_requested',
            'changed_by_name'     => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values'          => ['status' => $previousStatus],
            'new_values'          => [
                'status'           => $pendingStatus,
                'approval_type'    => $validated['type'],
                'approval_reason'  => $validated['reason'],
            ],
        ]);

        $typeLabel = $validated['type'] === 'fraud' ? 'Fraud' : 'Write Off';
        return back()->with('success', "Pengajuan {$typeLabel} berhasil dikirim dan menunggu persetujuan Administrator.");
    }

    public function approve(AssetApproval $approval)
    {
        if ($approval->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $staff = session('staff_it');
        $asset = $approval->asset;

        // Update asset status to the approved type
        $finalStatus = $approval->type; // 'fraud' or 'write_off'
        if ($asset) {
            $asset->status = $finalStatus;
            $asset->save();
        }

        // Update approval record
        $approval->update([
            'status'      => 'approved',
            'approved_by' => $staff['name'],
            'approved_at' => now(),
        ]);

        // Activity log
        if ($asset) {
            AssetHistory::create([
                'asset_id'            => $asset->id,
                'asset_name'          => $asset->name,
                'action'              => 'approval_approved',
                'changed_by_name'     => $staff['name'],
                'changed_by_position' => $staff['position'],
                'changed_by_location' => $staff['location'],
                'old_values'          => ['status' => $approval->previous_status],
                'new_values'          => [
                    'status'        => $finalStatus,
                    'approved_by'   => $staff['name'],
                    'approval_type' => $approval->type,
                ],
            ]);
        }

        $typeLabel = $approval->type === 'fraud' ? 'Fraud' : 'Write Off';
        return back()->with('success', "Pengajuan {$typeLabel} untuk aset \"{$approval->asset_name}\" telah disetujui.");
    }

    /**
     * Teknisi: Submit a new replacement request.
     */
    public function storeReplacement(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10|max:1000',
            'ticket_id' => 'nullable|exists:maintenance_tickets,id',
        ]);

        $user = auth()->user();

        $existingPending = \App\Models\ReplacementRequest::where('asset_id', $asset->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingPending) {
            return back()->with('error', 'Aset ini sudah memiliki pengajuan penggantian (replacement) yang tertunda.');
        }

        \App\Models\ReplacementRequest::create([
            'asset_id' => $asset->id,
            'ticket_id' => $validated['ticket_id'] ?? null,
            'requested_by' => $user->id,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        return back()->with('success', 'Pengajuan penggantian aset berhasil dikirim ke Administrator.');
    }

    /**
     * Admin: Approve a replacement request.
     */
    public function approveReplacement(Request $request, \App\Models\ReplacementRequest $replacement)
    {
        if ($replacement->status !== 'pending') {
            return back()->with('error', 'Pengajuan replacement ini sudah diproses.');
        }

        $user = auth()->user();
        
        $replacement->update([
            'status' => 'approved',
            'resolved_by' => $user->id,
            'notes' => $request->input('notes'),
            'resolved_at' => now(),
        ]);

        $asset = $replacement->asset;
        if ($asset) {
            $asset->status = 'rusak';
            $asset->save();
        }

        return back()->with('success', 'Pengajuan penggantian aset berhasil disetujui. Status aset otomatis diubah menjadi Rusak.');
    }

    /**
     * Admin: Reject a replacement request.
     */
    public function rejectReplacement(Request $request, \App\Models\ReplacementRequest $replacement)
    {
        $request->validate([
            'notes' => 'required|string|min:5|max:500',
        ]);

        if ($replacement->status !== 'pending') {
            return back()->with('error', 'Pengajuan replacement ini sudah diproses.');
        }

        $user = auth()->user();

        $replacement->update([
            'status' => 'rejected',
            'resolved_by' => $user->id,
            'notes' => $request->input('notes'),
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Pengajuan penggantian aset ditolak.');
    }

    /**
     * Admin: Reject an approval request.
     */
    public function reject(Request $request, AssetApproval $approval)
    {
        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:500',
        ]);

        if ($approval->status !== 'pending') {
            return back()->with('error', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $staff = session('staff_it');
        $asset = $approval->asset;

        // Revert asset status to previous
        if ($asset) {
            $asset->status = $approval->previous_status;
            $asset->save();
        }

        // Update approval record
        $approval->update([
            'status'           => 'rejected',
            'rejected_by'      => $staff['name'],
            'rejected_at'      => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        // Activity log
        if ($asset) {
            AssetHistory::create([
                'asset_id'            => $asset->id,
                'asset_name'          => $asset->name,
                'action'              => 'approval_rejected',
                'changed_by_name'     => $staff['name'],
                'changed_by_position' => $staff['position'],
                'changed_by_location' => $staff['location'],
                'old_values'          => ['status' => $asset->status],
                'new_values'          => [
                    'status'           => $approval->previous_status,
                    'rejected_by'      => $staff['name'],
                    'rejection_reason' => $request->rejection_reason,
                    'approval_type'    => $approval->type,
                ],
            ]);
        }

        $typeLabel = $approval->type === 'fraud' ? 'Fraud' : 'Write Off';
        return back()->with('success', "Pengajuan {$typeLabel} untuk aset \"{$approval->asset_name}\" telah ditolak.");
    }
}

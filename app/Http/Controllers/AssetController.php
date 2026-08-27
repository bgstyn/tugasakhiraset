<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetApproval;
use App\Models\AssetHistory;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * Display a listing of the assets with search and status filter.
     */
    public function index(Request $request)
    {
        $query = Asset::query();

        // Search by name, asset_id, government_inventory_number, current_user, brand, model, category, serial_number
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('asset_id', 'like', "%{$search}%")
                  ->orWhere('government_inventory_number', 'like', "%{$search}%")
                  ->orWhere('current_user', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('rfid_uid', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by location (mapped from locations table)
        if ($request->filled('location_id')) {
            $loc = \App\Models\Location::find($request->input('location_id'));
            if ($loc) {
                $query->where('room', $loc->kode_ruangan)
                      ->where('floor', $loc->lantai);
            }
        }

        // Apply Quick Filter
        if ($request->filled('quick_filter')) {
            $filter = $request->input('quick_filter');
            switch ($filter) {
                case 'hari_ini':
                    $query->whereDate('created_at', \Carbon\Carbon::today());
                    break;
                case '7_hari':
                    $query->where('created_at', '>=', \Carbon\Carbon::now()->subDays(7));
                    break;
                case '30_hari':
                    $query->where('created_at', '>=', \Carbon\Carbon::now()->subDays(30));
                    break;
                case 'no_rfid':
                    $query->where(function ($q) {
                        $q->whereNull('rfid_uid')->orWhere('rfid_uid', '');
                    });
                    break;
                case 'pending_approval':
                    $query->whereIn('status', ['pending_fraud_approval', 'pending_write_off_approval']);
                    break;
                case 'pending_fraud':
                    $query->where('status', 'pending_fraud_approval');
                    break;
                case 'pending_write_off':
                    $query->where('status', 'pending_write_off_approval');
                    break;
            }
        }

        // Apply Sorting
        $sortBy = $request->input('sort_by', 'terbaru');
        if ($request->input('quick_filter') === 'terbaru') {
            $sortBy = 'terbaru';
        }

        switch ($sortBy) {
            case 'terlama':
                $query->orderBy('created_at', 'asc');
                break;
            case 'nama_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'nama_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'inv_code':
                $query->orderBy('asset_id', 'asc');
                break;
            case 'status':
                $query->orderBy('status', 'asc');
                break;
            case 'terbaru':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $assets = $query->paginate(10)->withQueryString();
        $locations = \App\Models\Location::orderBy('lantai')->orderBy('kode_ruangan')->get();

        return view('assets.index', compact('assets', 'locations'));
    }

    /**
     * Show the form for creating a new asset.
     */
    public function create()
    {
        $locations = \App\Models\Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        return view('assets.create', compact('locations'));
    }

    /**
     * Store a newly created asset in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'government_inventory_number' => 'required|string|unique:assets,government_inventory_number|max:100',
            'serial_number' => 'nullable|string|unique:assets,serial_number|max:100',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 50),
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'room' => 'required|string|max:100',
            'current_user' => 'nullable|string|max:100',
            'status' => 'required|in:standby,digunakan,maintenance,rusak,fraud,write_off',
            'category' => 'required|string|max:100',
            'brand' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'model' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'specification' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/assets'), $fileName);
            $validated['photo'] = 'uploads/assets/' . $fileName;
        }

        $asset = Asset::create($validated);

        // Record History
        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'create',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'new_values' => $validated,
        ]);

        return redirect()->route('assets.index')
            ->with('success', 'Aset baru berhasil ditambahkan!')
            ->with('newly_created_id', $asset->id)
            ->with('newly_created_name', $asset->name);
    }

    /**
     * Display the specified asset.
     */
    public function show(Asset $asset)
    {
        // Get history logs for this specific asset
        $histories = $asset->histories()->orderBy('created_at', 'desc')->get();
        // Get approval history for this asset
        $approvals = $asset->approvals()->orderBy('created_at', 'desc')->get();
        // Get bundles this asset belongs to
        $asset->load('bundles.location');
        return view('assets.show', compact('asset', 'histories', 'approvals'));
    }

    /**
     * Show the form for editing the specified asset.
     */
    public function edit(Asset $asset)
    {
        $locations = \App\Models\Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        return view('assets.edit', compact('asset', 'locations'));
    }

    /**
     * Update the specified asset in storage.
     */
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'government_inventory_number' => 'required|string|unique:assets,government_inventory_number,' . $asset->id . '|max:100',
            'serial_number' => 'nullable|string|unique:assets,serial_number,' . $asset->id . '|max:100',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 50),
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'room' => 'required|string|max:100',
            'current_user' => 'nullable|string|max:100',
            'status' => 'required|in:standby,digunakan,maintenance,rusak,fraud,write_off',
            'category' => 'required|string|max:100',
            'brand' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'model' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'specification' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('photo')) {
            if ($asset->photo && file_exists(public_path($asset->photo))) {
                @unlink(public_path($asset->photo));
            }
            $file = $request->file('photo');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/assets'), $fileName);
            $validated['photo'] = 'uploads/assets/' . $fileName;
        }

        $oldValues = $asset->only([
            'name', 'asset_id', 'government_inventory_number', 'serial_number', 'year', 'building', 'floor', 'room', 'current_user', 'status',
            'category', 'brand', 'model', 'specification', 'photo'
        ]);
        
        $asset->update($validated);

        // Record History
        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'update',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values' => $oldValues,
            'new_values' => $validated,
        ]);

        return redirect()->route('assets.show', $asset->id)->with('success', 'Data aset berhasil diperbarui!');
    }

    /**
     * Quick status update for an asset.
     * Fraud and Write Off by non-admin users go through the approval flow.
     */
    public function updateStatus(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'status' => 'required|in:standby,digunakan,maintenance,rusak,fraud,write_off,pending_fraud_approval,pending_write_off_approval',
        ]);

        $staff   = session('staff_it');
        $isAdmin = auth()->user()?->isAdmin();

        // Non-admin trying to set Fraud or Write Off → route to approval flow
        if (!$isAdmin && in_array($validated['status'], ['fraud', 'write_off'])) {
            // Block if already pending
            if (in_array($asset->status, ['pending_fraud_approval', 'pending_write_off_approval'])) {
                return back()->with('error', 'Aset ini sudah memiliki pengajuan yang sedang menunggu persetujuan Administrator.');
            }
            return back()->with('need_approval', $validated['status']);
        }

        $oldValues = ['status' => $asset->status];
        $asset->status = $validated['status'];
        $asset->save();

        // Record History
        AssetHistory::create([
            'asset_id'            => $asset->id,
            'asset_name'          => $asset->name,
            'action'              => 'update',
            'changed_by_name'     => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values'          => $oldValues,
            'new_values'          => ['status' => $validated['status']],
        ]);

        return back()->with('success', 'Status aset berhasil diubah menjadi ' . $validated['status'] . '!');
    }

    /**
     * Remove the specified asset from storage.
     */
    public function destroy(Asset $asset)
    {
        $oldValues = $asset->only([
            'name', 'asset_id', 'government_inventory_number', 'serial_number', 'year', 'building', 'floor', 'room', 'current_user', 'status',
            'category', 'brand', 'model', 'specification'
        ]);
        $assetName = $asset->name;

        // Note: Relation has Cascade/Set Null constraint set up in migration
        $asset->delete();

        // Record History (asset_id is set to null, but asset_name is retained)
        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => null,
            'asset_name' => $assetName,
            'action' => 'delete',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values' => $oldValues,
        ]);

        return redirect()->route('assets.index')->with('success', 'Aset berhasil dihapus dari sistem!');
    }

    /**
     * Show QR Code camera scanner page.
     */
    public function scan()
    {
        return view('assets.scan');
    }

    /**
     * Look up an asset by scanned QR Code / barcode / asset_id / RFID from the web scanner.
     */
    public function lookupAsset(Request $request)
    {
        $code = $request->input('code') ?: $request->input('asset_id') ?: $request->input('code_asset') ?: $request->input('rfid_uid');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => 'Kode aset tidak boleh kosong.'
            ], 400);
        }

        $cleanCode = trim($code);
        if (str_contains($cleanCode, '/a/')) {
            $parts = explode('/a/', $cleanCode);
            $cleanCode = end($parts);
        } elseif (str_contains($cleanCode, '/assets/code/')) {
            $parts = explode('/assets/code/', $cleanCode);
            $cleanCode = end($parts);
        } elseif (str_contains($cleanCode, '/assets/')) {
            $parts = explode('/assets/', $cleanCode);
            $cleanCode = end($parts);
        }
        $cleanCode = strtok($cleanCode, '?#');

        // Look up by asset_id, rfid_uid, serial_number, or government_inventory_number
        $asset = Asset::where('asset_id', $cleanCode)
            ->orWhere('asset_id', $code)
            ->orWhere('rfid_uid', $code)
            ->orWhere('serial_number', $cleanCode)
            ->orWhere('government_inventory_number', $cleanCode)
            ->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => "Aset '{$cleanCode}' tidak ditemukan dalam database."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Aset berhasil ditemukan!',
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'status' => $asset->status,
                'building' => $asset->building ?? '-',
                'floor' => $asset->floor ?? '-',
                'room' => $asset->room ?? '-',
                'location' => $asset->room ?? '-',
                'current_user' => $asset->current_user ?? '-',
                'url' => route('assets.show', $asset->id)
            ],
            'redirect_url' => route('assets.show', $asset->id)
        ]);
    }

    /**
     * Proxy Raspberry Pi MJPEG stream over HTTPS to avoid Mixed Content browser blocking.
     */
    public function streamProxy(Request $request)
    {
        $ip = $request->query('ip', '192.168.100.107');
        $url = "http://{$ip}:5000/video_feed";

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
            ]
        ]);

        $stream = @fopen($url, 'rb', false, $ctx);
        if (!$stream) {
            return response()->json(['error' => 'Kamera RasPi offline atau tidak dapat dijangkau.'], 503);
        }

        return response()->stream(function() use ($stream) {
            while (!feof($stream)) {
                $buffer = @fread($stream, 8192);
                if ($buffer === false || $buffer === '') {
                    break;
                }
                echo $buffer;
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
            @fclose($stream);
        }, 200, [
            'Content-Type' => 'multipart/x-mixed-replace; boundary=frame',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }


    /**
     * Display the global change history logs.
     */
    public function history()
    {
        $histories = AssetHistory::orderBy('created_at', 'desc')->paginate(15);
        return view('assets.history', compact('histories'));
    }

    /**
     * Export all assets to CSV format.
     */
    public function export()
    {
        $fileName = 'it-assets-export-' . date('Y-m-d-His') . '.csv';
        $assets = Asset::all();

        $headers = array(
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        );

        $columns = array(
            'ID', 'ID Asset TI', 'Nomor Inventaris Kementerian', 'Serial Number', 'Nama Aset', 'Kategori', 'Brand', 'Model', 'Spesifikasi', 
            'Gedung', 'Lantai', 'Ruangan', 'User Saat Ini', 'Status', 'Tanggal Input', 'Terakhir Diperbarui'
        );

        $callback = function() use($assets, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($assets as $asset) {
                fputcsv($file, array(
                    $asset->id,
                    $asset->asset_id,
                    $asset->government_inventory_number ?? '-',
                    $asset->serial_number ?? '-',
                    $asset->name,
                    $asset->category ?? '-',
                    $asset->brand ?? '-',
                    $asset->model ?? '-',
                    $asset->specification ?? '-',
                    $asset->building ?? '-',
                    $asset->floor ?? '-',
                    $asset->room ?? '-',
                    $asset->current_user ?? '-',
                    $asset->status,
                    $asset->created_at->format('Y-m-d H:i:s'),
                    $asset->updated_at->format('Y-m-d H:i:s')
                ));
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Redirect short URL QR scans to the official public show page.
     */
    public function showByCode($asset_id)
    {
        $asset = Asset::where('asset_id', $asset_id)->firstOrFail();
        
        if (auth()->check()) {
            return redirect()->route('assets.show', $asset->id);
        }

        return view('assets.public_show', compact('asset'));
    }

    /**
     * Register or update an RFID UID for the asset via web interface.
     */
    public function registerRfid(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'rfid_uid' => 'required|string|max:100|unique:assets,rfid_uid,' . $asset->id,
        ]);

        $oldValues = [
            'rfid_uid' => $asset->rfid_uid,
            'rfid_status' => $asset->rfid_status
        ];

        $asset->rfid_uid = $validated['rfid_uid'];
        $asset->rfid_status = 'aktif';
        $asset->save();

        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'update',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values' => $oldValues,
            'new_values' => [
                'rfid_uid' => $validated['rfid_uid'],
                'rfid_status' => 'aktif'
            ],
        ]);

        return back()->with('success', 'RFID UID berhasil didaftarkan ke aset!');
    }

    /**
     * Delete/deregister the RFID UID from the asset (Administrator only).
     */
    public function deleteRfid(Asset $asset)
    {
        if (!auth()->user()?->isAdmin()) {
            return back()->with('error', 'Hanya Administrator yang diperbolehkan menghapus RFID.');
        }

        $oldValues = [
            'rfid_uid' => $asset->rfid_uid,
            'rfid_status' => $asset->rfid_status
        ];

        $asset->rfid_uid = null;
        $asset->rfid_status = 'belum_terdaftar';
        $asset->save();

        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'update',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values' => $oldValues,
            'new_values' => [
                'rfid_uid' => null,
                'rfid_status' => 'belum_terdaftar'
            ],
        ]);

        return back()->with('success', 'RFID UID berhasil dihapus dari aset.');
    }

    /**
     * Toggle the RFID status between active and inactive.
     */
    public function toggleRfid(Asset $asset)
    {
        if (empty($asset->rfid_uid)) {
            return back()->with('error', 'RFID belum didaftarkan pada aset ini.');
        }

        $oldValues = ['rfid_status' => $asset->rfid_status];
        $newStatus = $asset->rfid_status === 'aktif' ? 'nonaktif' : 'aktif';
        
        $asset->rfid_status = $newStatus;
        $asset->save();

        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'update',
            'changed_by_name' => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values' => $oldValues,
            'new_values' => ['rfid_status' => $newStatus],
        ]);

        $message = $newStatus === 'aktif' ? 'RFID berhasil diaktifkan kembali!' : 'RFID berhasil dinonaktifkan!';
        return back()->with('success', $message);
    }

    /**
     * Display a paginated log list of RFID change history logs.
     */
    public function rfidHistory()
    {
        $histories = AssetHistory::where(function($query) {
            $query->where('old_values', 'like', '%rfid_uid%')
                  ->orWhere('new_values', 'like', '%rfid_uid%')
                  ->orWhere('old_values', 'like', '%rfid_status%')
                  ->orWhere('new_values', 'like', '%rfid_status%')
                  ->orWhere('changed_by_name', 'like', '%RFID%');
        })->orderBy('created_at', 'desc')->paginate(15);

        return view('assets.rfid.history', compact('histories'));
    }

    /**
     * Show the RFID scan validator screen.
     */
    public function showRfidValidator(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $rfidUid = $request->input('rfid_uid');
            $asset = Asset::where('rfid_uid', $rfidUid)->first();

            if (!$asset) {
                return response()->json([
                    'success' => false,
                    'message' => 'RFID belum terdaftar.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'asset_id' => $asset->asset_id,
                    'rfid_uid' => $asset->rfid_uid,
                    'status' => $asset->status,
                    'location' => $asset->room ?? '-',
                    'building' => $asset->building ?? '-',
                    'floor' => $asset->floor ?? '-',
                    'current_user' => $asset->current_user ?? '-',
                    'url' => route('assets.show', $asset->id)
                ]
            ]);
        }

        return view('assets.rfid.validate');
    }
}

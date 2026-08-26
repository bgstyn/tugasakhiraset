<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\Location;
use Illuminate\Http\Request;

class BulkAssetController extends Controller
{
    /**
     * Show the bulk asset creation form.
     */
    public function create()
    {
        $locations = Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        return view('admin.bulk.create', compact('locations'));
    }

    /**
     * Preview the bulk assets before storing them.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'brand' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'model' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'count' => 'required|integer|min:1|max:100',
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'room' => 'required|string|max:100',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 50),
            'status' => 'required|in:standby,digunakan,maintenance,rusak,fraud,write_off',
            'specification' => 'nullable|string',
            'current_user' => 'nullable|string|max:100',
        ]);

        $year = $validated['year'];
        $prefix = "TIPNP-" . $year;

        // Retrieve the highest existing inventory code for this prefix
        $latestAsset = Asset::where('asset_id', 'like', $prefix . '-%')
            ->orderBy('asset_id', 'desc')
            ->first();

        $startNum = 1;
        if ($latestAsset && preg_match('/-(\d+)$/', $latestAsset->asset_id, $matches)) {
            $startNum = (int)$matches[1] + 1;
        }

        $previewItems = [];
        $count = (int)$validated['count'];

        for ($i = 0; $i < $count; $i++) {
            $num = $startNum + $i;
            $assetId = $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
            
            $previewItems[] = [
                'asset_id' => $assetId,
            ];
        }

        return view('admin.bulk.preview', compact('validated', 'previewItems'));
    }

    /**
     * Store the bulk assets in database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'brand' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'model' => 'required_unless:category,PC Desktop|nullable|string|max:100',
            'count' => 'required|integer|min:1|max:100',
            'building' => 'required|string|max:100',
            'floor' => 'required|string|max:50',
            'room' => 'required|string|max:100',
            'year' => 'required|integer|min:2000|max:' . (date('Y') + 50),
            'status' => 'required|in:standby,digunakan,maintenance,rusak,fraud,write_off',
            'specification' => 'nullable|string',
            'current_user' => 'nullable|string|max:100',
            
            'gov_codes' => 'required|array',
            'gov_codes.*' => 'required|string|distinct|unique:assets,government_inventory_number|max:100',
            'serials' => 'nullable|array',
            'serials.*' => 'nullable|string|distinct|unique:assets,serial_number|max:100',
        ]);

        $createdIds = \DB::transaction(function () use ($validated) {
            $count = (int)$validated['count'];
            $ids = [];
            $staff = session('staff_it');
            $year = $validated['year'];
            $prefix = "TIPNP-" . $year;

            // Fetch start number with lock
            $latest = Asset::where('asset_id', 'like', $prefix . '-%')
                ->lockForUpdate()
                ->orderBy('asset_id', 'desc')
                ->first();

            $startNum = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest->asset_id, $matches)) {
                $startNum = (int)$matches[1] + 1;
            }

            for ($i = 0; $i < $count; $i++) {
                $num = $startNum + $i;
                $assetId = $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
                while (Asset::where('asset_id', $assetId)->exists()) {
                    $num++;
                    $assetId = $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
                }

                $asset = Asset::create([
                    'name' => $validated['name'],
                    'category' => $validated['category'],
                    'brand' => $validated['brand'] ?? null,
                    'model' => $validated['model'] ?? null,
                    'building' => $validated['building'],
                    'floor' => $validated['floor'],
                    'room' => $validated['room'],
                    'year' => $validated['year'],
                    'status' => $validated['status'],
                    'specification' => $validated['specification'] ?? null,
                    'asset_id' => $assetId,
                    'government_inventory_number' => $validated['gov_codes'][$i],
                    'serial_number' => $validated['serials'][$i] ?? null,
                    'current_user' => $validated['current_user'] ?? null,
                ]);

                $ids[] = $asset->id;

                // Record History log
                AssetHistory::create([
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'action' => 'create',
                    'changed_by_name' => $staff['name'],
                    'changed_by_position' => $staff['position'],
                    'changed_by_location' => $staff['location'],
                    'new_values' => $asset->toArray(),
                ]);
            }

            return $ids;
        });

        return redirect()->route('admin.assets.bulk.success')
                         ->with('bulk_created_ids', $createdIds);
     }

    /**
     * Show success summary page for bulk creation.
     */
    public function success()
    {
        $ids = session('bulk_created_ids');
        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('admin.assets.bulk.create')
                             ->with('error', 'Tidak ada data aset baru yang ditemukan dalam sesi ini.');
        }

        $assets = Asset::whereIn('id', $ids)->get();

        return view('admin.bulk.success', compact('assets'));
    }




}

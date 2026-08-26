<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBundle;
use App\Models\AssetHistory;
use App\Models\Location;
use Illuminate\Http\Request;

class AssetBundleController extends Controller
{
    /**
     * Display a listing of all bundles.
     */
    public function index(Request $request)
    {
        $query = AssetBundle::with('location')->withCount('assets');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $bundles = $query->orderBy('created_at', 'desc')->paginate(12)->withQueryString();

        return view('bundles.index', compact('bundles'));
    }

    /**
     * Show the form for creating a new bundle.
     */
    public function create()
    {
        $locations = Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        $assets    = Asset::orderBy('name')
                         ->get()
                         ->groupBy('category');

        return view('bundles.create', compact('locations', 'assets'));
    }

    /**
     * Store a newly created bundle in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:200',
            'location_id' => 'nullable|exists:locations,id',
            'description' => 'nullable|string|max:1000',
            'asset_ids'   => 'nullable|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $bundle = AssetBundle::create([
            'name'        => $validated['name'],
            'location_id' => $validated['location_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        if (!empty($validated['asset_ids'])) {
            $bundle->assets()->sync($validated['asset_ids']);
        }

        // Log activity
        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id'            => null,
            'asset_name'          => 'Bundle: ' . $bundle->name,
            'action'              => 'create',
            'changed_by_name'     => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values'          => null,
            'new_values'          => [
                'bundle_code' => $bundle->code,
                'asset_count' => count($validated['asset_ids'] ?? []),
            ],
        ]);

        return redirect()->route('bundles.show', $bundle)
                         ->with('success', "Bundle \"{$bundle->name}\" ({$bundle->code}) berhasil dibuat!");
    }

    /**
     * Display the specified bundle detail.
     */
    public function show(AssetBundle $bundle)
    {
        $bundle->load(['assets', 'location']);
        return view('bundles.show', compact('bundle'));
    }

    /**
     * Show the form for editing a bundle.
     */
    public function edit(AssetBundle $bundle)
    {
        $bundle->load('assets');
        $locations     = Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        $assets        = Asset::orderBy('name')->get()->groupBy('category');
        $selectedIds   = $bundle->assets->pluck('id')->toArray();

        return view('bundles.edit', compact('bundle', 'locations', 'assets', 'selectedIds'));
    }

    /**
     * Update the specified bundle in storage.
     */
    public function update(Request $request, AssetBundle $bundle)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:200',
            'location_id' => 'nullable|exists:locations,id',
            'description' => 'nullable|string|max:1000',
            'asset_ids'   => 'nullable|array',
            'asset_ids.*' => 'exists:assets,id',
        ]);

        $bundle->update([
            'name'        => $validated['name'],
            'location_id' => $validated['location_id'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        // Sync assets (removes old, adds new)
        $bundle->assets()->sync($validated['asset_ids'] ?? []);

        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id'            => null,
            'asset_name'          => 'Bundle: ' . $bundle->name,
            'action'              => 'update',
            'changed_by_name'     => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values'          => null,
            'new_values'          => [
                'bundle_code' => $bundle->code,
                'asset_count' => count($validated['asset_ids'] ?? []),
            ],
        ]);

        return redirect()->route('bundles.show', $bundle)
                         ->with('success', "Bundle \"{$bundle->name}\" berhasil diperbarui!");
    }

    /**
     * Remove the specified bundle (assets are NOT deleted).
     */
    public function destroy(AssetBundle $bundle)
    {
        $bundleName = $bundle->name;
        $bundleCode = $bundle->code;

        // Detach all assets first (cascade will handle this, but explicit is clearer)
        $bundle->assets()->detach();
        $bundle->delete();

        $staff = session('staff_it');
        AssetHistory::create([
            'asset_id'            => null,
            'asset_name'          => 'Bundle: ' . $bundleName,
            'action'              => 'delete',
            'changed_by_name'     => $staff['name'],
            'changed_by_position' => $staff['position'],
            'changed_by_location' => $staff['location'],
            'old_values'          => ['bundle_code' => $bundleCode],
            'new_values'          => null,
        ]);

        return redirect()->route('bundles.index')
                         ->with('success', "Bundle \"{$bundleName}\" berhasil dihapus. Aset individual tidak terpengaruh.");
    }
}

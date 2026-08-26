<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Display a listing of the locations.
     */
    public function index(Request $request)
    {
        $query = Location::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('kode_ruangan', 'like', "%{$search}%")
                  ->orWhere('nama_ruangan', 'like', "%{$search}%")
                  ->orWhere('lantai', 'like', "%{$search}%");
            });
        }

        $locations = $query->orderBy('lantai')->orderBy('kode_ruangan')->paginate(10)->withQueryString();

        return view('locations.index', compact('locations'));
    }

    /**
     * Show the form for creating a new location.
     */
    public function create()
    {
        return view('locations.create');
    }

    /**
     * Store a newly created location in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_ruangan' => 'required|string|max:50|unique:locations,kode_ruangan',
            'nama_ruangan' => 'required|string|max:100',
            'lantai' => 'required|integer|min:1|max:10',
        ]);

        Location::create($validated);

        return redirect()->route('locations.index')->with('success', 'Lokasi kampus baru berhasil ditambahkan!');
    }

    /**
     * Show the form for editing the specified location.
     */
    public function edit(Location $location)
    {
        return view('locations.edit', compact('location'));
    }

    /**
     * Update the specified location in storage.
     */
    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'kode_ruangan' => 'required|string|max:50|unique:locations,kode_ruangan,' . $location->id,
            'nama_ruangan' => 'required|string|max:100',
            'lantai' => 'required|integer|min:1|max:10',
        ]);

        $location->update($validated);

        return redirect()->route('locations.index')->with('success', 'Data lokasi berhasil diperbarui!');
    }

    /**
     * Remove the specified location from storage.
     */
    public function destroy(Location $location)
    {
        // Check if there are any assets associated with this location
        if ($location->assets()->exists()) {
            return back()->with('error', 'Lokasi tidak dapat dihapus karena masih digunakan oleh aset IT!');
        }

        $location->delete();

        return redirect()->route('locations.index')->with('success', 'Lokasi berhasil dihapus!');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class TeknisiManagementController extends Controller
{
    /**
     * Display a listing of all teknisi accounts.
     */
    public function index()
    {
        $teknisiList = User::where('role', 'teknisi')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.teknisi.index', compact('teknisiList'));
    }

    /**
     * Show the form for creating a new teknisi account.
     */
    public function create()
    {
        $locations = \App\Models\Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        return view('admin.teknisi.create', compact('locations'));
    }

    /**
     * Store a newly created teknisi account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique:users,username|alpha_dash',
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'position' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => 'teknisi',
            'position' => $validated['position'] ?? null,
            'location' => $validated['location'] ?? null,
        ]);

        return redirect()->route('teknisi.index')
            ->with('success', 'Akun teknisi "' . $validated['name'] . '" berhasil dibuat.');
    }

    /**
     * Show the form for editing a teknisi account.
     */
    public function edit(User $teknisi)
    {
        // Ensure we can only edit teknisi accounts
        if (!$teknisi->isTeknisi()) {
            abort(403, 'Anda hanya dapat mengedit akun teknisi.');
        }

        $locations = \App\Models\Location::orderBy('lantai')->orderBy('kode_ruangan')->get();
        return view('admin.teknisi.edit', compact('teknisi', 'locations'));
    }

    /**
     * Update the specified teknisi account.
     */
    public function update(Request $request, User $teknisi)
    {
        if (!$teknisi->isTeknisi()) {
            abort(403, 'Anda hanya dapat mengedit akun teknisi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash|unique:users,username,' . $teknisi->id,
            'position' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:100',
        ]);

        $teknisi->update($validated);

        return redirect()->route('teknisi.index')
            ->with('success', 'Akun teknisi "' . $validated['name'] . '" berhasil diperbarui.');
    }

    /**
     * Remove the specified teknisi account.
     */
    public function destroy(User $teknisi)
    {
        if (!$teknisi->isTeknisi()) {
            abort(403, 'Anda hanya dapat menghapus akun teknisi.');
        }

        $name = $teknisi->name;
        $teknisi->delete();

        return redirect()->route('teknisi.index')
            ->with('success', 'Akun teknisi "' . $name . '" berhasil dihapus.');
    }

    /**
     * Reset the password of a teknisi account.
     */
    public function resetPassword(Request $request, User $teknisi)
    {
        if (!$teknisi->isTeknisi()) {
            abort(403, 'Anda hanya dapat mereset password akun teknisi.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $teknisi->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('teknisi.index')
            ->with('success', 'Password teknisi "' . $teknisi->name . '" berhasil direset.');
    }
}

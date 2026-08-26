<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * StaffController is deprecated.
 * Staff identity is now derived from the authenticated user.
 * This controller is kept only for backward compatibility of any external references.
 */
class StaffController extends Controller
{
    /**
     * Redirect to login page (replaces old staff session setup).
     */
    public function create()
    {
        return redirect()->route('login');
    }

    /**
     * Redirect to login page.
     */
    public function store(Request $request)
    {
        return redirect()->route('login');
    }

    /**
     * Logout the user (replaces old staff session destroy).
     */
    public function destroy()
    {
        return redirect()->route('logout');
    }
}

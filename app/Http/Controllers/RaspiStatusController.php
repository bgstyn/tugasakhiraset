<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class RaspiStatusController extends Controller
{
    public function heartbeat(Request $request)
    {
        $status = $request->validate([
            'mode' => 'required|in:check,register,invalid',
            'camera' => 'required|in:ready,error,disabled',
            'rfid' => 'required|in:ready,error,disabled',
            'pending_count' => 'present|nullable|integer|min:0|max:1000000',
        ]);
        Cache::put('raspi_heartbeat', $status + ['last_seen_at' => now()->toIso8601String()], now()->addDay());

        return response()->json(['success' => true]);
    }

    public function show()
    {
        $status = Cache::get('raspi_heartbeat');
        $online = $status !== null && now()->timestamp - \Carbon\Carbon::parse($status['last_seen_at'])->timestamp <= 35;

        return response()->json(['success' => true, 'online' => $online, 'data' => $status])
            ->header('Cache-Control', 'no-store');
    }
}

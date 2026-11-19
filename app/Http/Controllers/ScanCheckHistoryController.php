<?php

namespace App\Http\Controllers;

use App\Services\ScanCheckHistory;
use Illuminate\Http\Request;

class ScanCheckHistoryController extends Controller
{
    public function index(Request $request, ScanCheckHistory $history)
    {
        $validated = $request->validate(['after_id' => 'sometimes|integer|min:0']);
        $cursor = (int) ($validated['after_id'] ?? 0);
        $records = $history->since($cursor);

        return response()->json([
            'success' => true,
            'data' => $records,
            'latest_id' => $records[0]['id'] ?? $cursor,
            'limit' => ScanCheckHistory::LIMIT,
        ])->header('Cache-Control', 'no-store');
    }
}

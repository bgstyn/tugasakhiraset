<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RfidController extends Controller
{
    /**
     * API Endpoint for Raspberry Pi or RFID scanner to lookup or update assets.
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rfid_uid' => 'nullable|string',
            'asset_id' => 'nullable|required_without:rfid_uid|string',
            'status' => 'nullable|in:standby,digunakan,maintenance,rusak,fraud,write_off',
            'building' => 'nullable|string|max:100',
            'floor' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'current_user' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 400);
        }

        $rfidUid = $request->input('rfid_uid');
        $assetId = $request->input('asset_id');
        $asset = null;

        if ($rfidUid) {
            $asset = Asset::where('rfid_uid', $rfidUid)->first();
            if (!$asset) {
                return response()->json([
                    'success' => false,
                    'message' => "Aset dengan RFID UID '{$rfidUid}' belum terdaftar."
                ], 404);
            }
        } elseif ($assetId) {
            // Clean up asset_id if full QR Code URL was sent (e.g. http://192.168.100.105:8000/a/TIPNP-2025-0001)
            $cleanAssetId = trim($assetId);
            if (str_contains($cleanAssetId, '/a/')) {
                $parts = explode('/a/', $cleanAssetId);
                $cleanAssetId = end($parts);
            } elseif (str_contains($cleanAssetId, '/assets/code/')) {
                $parts = explode('/assets/code/', $cleanAssetId);
                $cleanAssetId = end($parts);
            } elseif (str_contains($cleanAssetId, '/assets/')) {
                $parts = explode('/assets/', $cleanAssetId);
                $cleanAssetId = end($parts);
            }
            $cleanAssetId = strtok($cleanAssetId, '?#');

            $asset = Asset::where('asset_id', $cleanAssetId)->orWhere('asset_id', $assetId)->first();
            if (!$asset) {
                return response()->json([
                    'success' => false,
                    'message' => "Aset dengan ID Asset TI '{$cleanAssetId}' tidak ditemukan."
                ], 404);
            }
        }


        $updated = false;
        $oldValues = $asset->only(['name', 'asset_id', 'current_user', 'year', 'building', 'floor', 'room', 'status', 'rfid_uid']);
        $newValues = [];

        // Check if status needs to be updated
        if ($request->filled('status') && $request->input('status') !== $asset->status) {
            $asset->status = $request->input('status');
            $newValues['status'] = $request->input('status');
            $updated = true;
        }

        // Check if location needs to be updated
        if ($request->filled('building') && $request->input('building') !== $asset->building) {
            $asset->building = $request->input('building');
            $newValues['building'] = $request->input('building');
            $updated = true;
        }
        if ($request->filled('floor') && $request->input('floor') !== $asset->floor) {
            $asset->floor = $request->input('floor');
            $newValues['floor'] = $request->input('floor');
            $updated = true;
        }
        if ($request->filled('room') && $request->input('room') !== $asset->room) {
            $asset->room = $request->input('room');
            $newValues['room'] = $request->input('room');
            $updated = true;
        }

        // Check if current_user needs to be updated
        if ($request->has('current_user') && $request->input('current_user') !== $asset->current_user) {
            $asset->current_user = $request->input('current_user');
            $newValues['current_user'] = $request->input('current_user');
            $updated = true;
        }

        if ($updated) {
            $asset->save();

            // Record history changes under Raspberry Pi RFID Agent
            AssetHistory::create([
                'asset_id' => $asset->id,
                'asset_name' => $asset->name,
                'action' => 'update',
                'changed_by_name' => 'Raspberry Pi RFID Reader',
                'changed_by_position' => 'IoT Scanner Agent',
                'changed_by_location' => $asset->room ?? 'Scanner',
                'old_values' => array_intersect_key($oldValues, $newValues),
                'new_values' => $newValues,
            ]);
        }

        $responseData = [
            'success' => true,
            'timestamp' => time(),
            'message' => $updated ? 'Aset berhasil diperbarui via RFID' : 'Aset berhasil ditemukan',
            'redirect_url' => route('assets.show', $asset->id),
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'status' => $asset->status,
                'building' => $asset->building,
                'floor' => $asset->floor,
                'room' => $asset->room,
                'current_user' => $asset->current_user,
                'updated' => $updated
            ]
        ];

        // Store latest scan in cache for real-time web UI auto-redirection
        \Illuminate\Support\Facades\Cache::put('latest_asset_scan', $responseData, 120);

        return response()->json($responseData, 200);
    }

    /**
     * API / Web Endpoint to poll the latest scan result from Raspberry Pi.
     */
    public function getLatestScan()
    {
        $latest = \Illuminate\Support\Facades\Cache::get('latest_asset_scan');
        return response()->json([
            'success' => !!$latest,
            'data' => $latest
        ]);
    }


    /**
     * API Endpoint to link an RFID UID to a specific asset.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|string',
            'rfid_uid' => 'required|string|unique:assets,rfid_uid',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi pendaftaran gagal',
                'errors' => $validator->errors()
            ], 400);
        }

        $assetId = $request->input('asset_id');
        $rfidUid = $request->input('rfid_uid');

        $asset = Asset::where('asset_id', $assetId)->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => "Aset dengan ID Asset TI '{$assetId}' tidak ditemukan."
            ], 404);
        }

        $oldValues = ['rfid_uid' => $asset->rfid_uid, 'rfid_status' => $asset->rfid_status];
        $asset->rfid_uid = $rfidUid;
        $asset->rfid_status = 'aktif';
        $asset->save();

        // Record history
        AssetHistory::create([
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'action' => 'update',
            'changed_by_name' => 'Raspberry Pi RFID Register',
            'changed_by_position' => 'IoT Registration Agent',
            'changed_by_location' => $asset->room ?? 'Scanner',
            'old_values' => $oldValues,
            'new_values' => ['rfid_uid' => $rfidUid, 'rfid_status' => 'aktif'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RFID UID berhasil didaftarkan ke aset',
            'redirect_url' => route('assets.show', $asset->id),
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'rfid_status' => $asset->rfid_status,
            ]
        ], 200);
    }

    /**
     * API Endpoint to validate an RFID UID.
     */
    public function validateRfid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'rfid_uid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 400);
        }

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
            'message' => 'RFID terdaftar.',
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'rfid_status' => $asset->rfid_status,
                'status' => $asset->status,
                'location' => $asset->room ?? '-',
                'building' => $asset->building ?? '-',
                'floor' => $asset->floor ?? '-',
                'current_user' => $asset->current_user ?? '-',
            ]
        ], 200);
    }

    /**
     * API Endpoint to retrieve asset details by RFID UID.
     */
    public function getAssetByRfid(Request $request)
    {
        $rfidUid = $request->query('rfid_uid') ?: $request->input('rfid_uid');

        if (!$rfidUid) {
            return response()->json([
                'success' => false,
                'message' => 'RFID UID wajib dikirimkan.'
            ], 400);
        }

        $asset = Asset::where('rfid_uid', $rfidUid)->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => "Aset dengan RFID UID '{$rfidUid}' tidak ditemukan."
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'rfid_status' => $asset->rfid_status,
                'status' => $asset->status,
                'category' => $asset->category,
                'brand' => $asset->brand,
                'model' => $asset->model,
                'building' => $asset->building,
                'floor' => $asset->floor,
                'room' => $asset->room,
                'current_user' => $asset->current_user,
            ]
        ], 200);
    }

    /**
     * API Endpoint to sync/retrieve all active RFID mappings.
     */
    public function sync(Request $request)
    {
        $assets = Asset::whereNotNull('rfid_uid')
            ->select(['id', 'name', 'asset_id', 'rfid_uid', 'rfid_status', 'status'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $assets
        ], 200);
    }

    /**
     * API Endpoint to bulk synchronize or register RFID tags from IoT controller database.
     */
    public function syncPost(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sync_data' => 'required|array',
            'sync_data.*.asset_id' => 'required|string',
            'sync_data.*.rfid_uid' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi sync gagal',
                'errors' => $validator->errors()
            ], 400);
        }

        $syncData = $request->input('sync_data');
        $updatedCount = 0;

        foreach ($syncData as $item) {
            $asset = Asset::where('asset_id', $item['asset_id'])->first();
            if ($asset) {
                // Skip if tag already registered to another asset to enforce uniqueness
                $exists = Asset::where('rfid_uid', $item['rfid_uid'])->where('id', '!=', $asset->id)->exists();
                if (!$exists && $asset->rfid_uid !== $item['rfid_uid']) {
                    $oldValues = ['rfid_uid' => $asset->rfid_uid, 'rfid_status' => $asset->rfid_status];
                    $asset->rfid_uid = $item['rfid_uid'];
                    $asset->rfid_status = 'aktif';
                    $asset->save();

                    AssetHistory::create([
                        'asset_id' => $asset->id,
                        'asset_name' => $asset->name,
                        'action' => 'update',
                        'changed_by_name' => 'Raspberry Pi RFID Sync',
                        'changed_by_position' => 'IoT Sync Agent',
                        'changed_by_location' => $asset->room ?? 'Scanner',
                        'old_values' => $oldValues,
                        'new_values' => ['rfid_uid' => $item['rfid_uid'], 'rfid_status' => 'aktif'],
                    ]);

                    $updatedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sinkronisasi berhasil. {$updatedCount} aset diperbarui."
        ], 200);
    }

    /**
     * API Endpoint for Raspberry Pi to upload live JPEG camera frames to AWS.
     * Uses direct file storage for reliability (database cache can't handle binary JPEG blobs well).
     */
    public function uploadFrame(Request $request)
    {
        if ($request->hasFile('frame')) {
            $file = $request->file('frame');
            $framePath = storage_path('app/raspi_frame.jpg');
            $metaPath = storage_path('app/raspi_frame_meta.json');

            // Ensure directory exists
            $dir = dirname($framePath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Write frame file atomically (write to temp then rename)
            $tmpPath = $framePath . '.tmp';
            file_put_contents($tmpPath, file_get_contents($file->getRealPath()));
            rename($tmpPath, $framePath);

            // Write metadata
            file_put_contents($metaPath, json_encode(['last_seen' => time()]));

            return response()->json(['success' => true], 200);
        }

        return response()->json(['success' => false, 'message' => 'No frame file uploaded.'], 400);
    }

    /**
     * API / Web Endpoint to serve the latest live JPEG camera frame.
     * Reads from file storage. Returns a placeholder image if offline (not JSON, to prevent img onerror).
     */
    public function getLiveFrame()
    {
        $framePath = storage_path('app/raspi_frame.jpg');
        $metaPath = storage_path('app/raspi_frame_meta.json');

        $isOnline = false;
        if (file_exists($framePath) && file_exists($metaPath)) {
            $meta = json_decode(file_get_contents($metaPath), true);
            $lastSeen = $meta['last_seen'] ?? 0;
            // Consider online if frame was received within last 15 seconds
            if ((time() - $lastSeen) < 15) {
                $isOnline = true;
            }
        }

        if ($isOnline) {
            $frame = file_get_contents($framePath);
            return response($frame, 200, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
                'X-Raspi-Status' => 'online',
            ]);
        }

        // Return a black placeholder JPEG with "OFFLINE" text instead of JSON error
        // This prevents the <img> tag from firing onerror permanently
        return response()->json([
            'success' => false,
            'message' => 'Kamera RasPi offline.',
            'status' => 'offline'
        ], 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'X-Raspi-Status' => 'offline',
        ]);
    }

    /**
     * Web Endpoint to stream live Raspberry Pi camera MJPEG frames on AWS over HTTPS.
     * Reads from file storage instead of cache.
     */
    public function getLiveStream()
    {
        $framePath = storage_path('app/raspi_frame.jpg');

        return response()->stream(function() use ($framePath) {
            for ($i = 0; $i < 300; $i++) {
                if (file_exists($framePath)) {
                    $frame = @file_get_contents($framePath);
                    if ($frame) {
                        echo "--frame\r\n";
                        echo "Content-Type: image/jpeg\r\n\r\n";
                        echo $frame;
                        echo "\r\n";
                    }
                }
                if (ob_get_level() > 0) {
                    @ob_flush();
                }
                @flush();
                usleep(100000); // ~10 FPS
            }
        }, 200, [
            'Content-Type' => 'multipart/x-mixed-replace; boundary=frame',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }
}


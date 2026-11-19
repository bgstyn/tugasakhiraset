<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class RfidController extends Controller
{
    public function readUid(Request $request)
    {
        $read = $request->validate([
            'rfid_uid' => 'required|string|max:100',
            'scan_id' => 'required|uuid',
        ]);
        if (Cache::add('rfid_read_seen:'.$read['scan_id'], true, 60)) {
            Cache::put('latest_rfid_read', $read + [
                'received_at' => (int) (microtime(true) * 1000),
            ], 30);
        }

        return response()->json(['success' => true]);
    }

    public function getLatestUid()
    {
        return response()->json([
            'success' => true,
            'data' => Cache::get('latest_rfid_read'),
        ])->header('Cache-Control', 'no-store');
    }

    /**
     * API Endpoint for Raspberry Pi or RFID scanner to lookup or update assets.
     */
    public function scan(Request $request)
    {
        // Support code_asset as alias for asset_id
        if (!$request->filled('asset_id') && $request->filled('code_asset')) {
            $request->merge(['asset_id' => $request->input('code_asset')]);
        }

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
        $assets = Asset::query()->when($request->attributes->has('rfid_event_id'), fn ($query) => $query->lockForUpdate());
        $asset = null;

        if ($rfidUid) {
            $asset = $assets->where('rfid_uid', $rfidUid)->first();
            if (!$asset) {
                return response()->json([
                    'success' => false,
                    'message' => "Aset dengan RFID UID '{$rfidUid}' belum terdaftar."
                ], 404);
            }
            if ($asset->rfid_status !== 'aktif') {
                return response()->json(['success' => false, 'code' => 'RFID_INACTIVE', 'message' => 'RFID tidak aktif. Aktifkan kembali melalui pengelolaan aset.'], 409);
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
            $cleanAssetId = rawurldecode(strtok($cleanAssetId, '?#') ?: '');

            $asset = $assets->where('asset_id', $cleanAssetId)->orWhere('asset_id', $assetId)->first();
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
            'received_at' => (int) (microtime(true) * 1000),
            'scan_id' => $request->attributes->get('rfid_event_id') ?? (string) Str::uuid(),
            'source' => $rfidUid ? 'rfid' : 'qr',
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

        $responseData['history'] = app(\App\Services\ScanCheckHistory::class)->record(
            $responseData['data'], $responseData['source'], $responseData['scan_id'], $responseData['received_at']
        );
        $this->publishScan($request, $responseData);

        return response()->json($responseData, 200);
    }

    private function publishScan(Request $request, array $responseData): void
    {
        if ($request->attributes->has('rfid_event_id')) {
            DB::afterCommit(fn () => Cache::put('latest_asset_scan', $responseData, 120));
        } else {
            Cache::put('latest_asset_scan', $responseData, 120);
        }
    }

    /**
     * API / Web Endpoint to poll the latest scan result from Raspberry Pi.
     */
    public function getLatestScan()
    {
        $latest = Cache::get('latest_asset_scan');
        return response()->json([
            'success' => !!$latest,
            'data' => $latest
        ])->header('Cache-Control', 'no-store');
    }


    /**
     * API Endpoint to link an RFID UID to a specific asset.
     */
    public function register(Request $request)
    {
        $fromOutbox = $request->attributes->has('rfid_event_id');
        $validator = Validator::make($request->all(), [
            'asset_id' => 'required|string',
            'rfid_uid' => $fromOutbox ? 'required|string|max:100' : 'required|string|unique:assets,rfid_uid',
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

        $asset = Asset::where('asset_id', $assetId)->when($fromOutbox, fn ($query) => $query->lockForUpdate())->first();

        if (!$asset) {
            return response()->json([
                'success' => false,
                'message' => "Aset dengan ID Asset TI '{$assetId}' tidak ditemukan."
            ], 404);
        }

        if ($fromOutbox && (
            (filled($asset->rfid_uid) && $asset->rfid_uid !== $rfidUid)
            || Asset::where('rfid_uid', $rfidUid)->whereKeyNot($asset->id)->exists()
        )) {
            return response()->json(['success' => false, 'message' => 'RFID atau aset sudah dipasangkan.'], 409);
        }

        if ($asset->rfid_uid !== $rfidUid || $asset->rfid_status !== 'aktif') {
            $oldValues = ['rfid_uid' => $asset->rfid_uid, 'rfid_status' => $asset->rfid_status];
            $asset->rfid_uid = $rfidUid;
            $asset->rfid_status = 'aktif';
            $asset->save();

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
        }

        $responseData = [
            'success' => true,
            'timestamp' => time(),
            'received_at' => (int) (microtime(true) * 1000),
            'scan_id' => $request->attributes->get('rfid_event_id') ?? (string) Str::uuid(),
            'source' => 'rfid',
            'message' => 'RFID UID berhasil didaftarkan ke aset',
            'redirect_url' => route('assets.show', $asset->id),
            'data' => [
                'id' => $asset->id,
                'name' => $asset->name,
                'asset_id' => $asset->asset_id,
                'rfid_uid' => $asset->rfid_uid,
                'rfid_status' => $asset->rfid_status,
                'status' => $asset->status,
                'building' => $asset->building,
                'floor' => $asset->floor,
                'room' => $asset->room,
                'current_user' => $asset->current_user,
            ]
        ];
        $this->publishScan($request, $responseData);

        return response()->json($responseData, 200);
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

        if ($asset->rfid_status !== 'aktif') {
            return response()->json(['success' => false, 'code' => 'RFID_INACTIVE', 'message' => 'RFID tidak aktif. Aktifkan kembali melalui pengelolaan aset.'], 409);
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

    public function uploadFrame(Request $request)
    {
        $validated = $request->validate([
            'frame' => 'required|file|mimetypes:image/jpeg|max:512',
        ]);

        $path = storage_path('app/raspi_frame.jpg');
        File::ensureDirectoryExists(dirname($path));
        File::replace($path, File::get($validated['frame']->getRealPath()), 0640);

        return response()->json(['success' => true]);
    }

    private function latestFrame(): ?string
    {
        $path = storage_path('app/raspi_frame.jpg');
        clearstatcache(true, $path);
        $lastSeen = @filemtime($path);

        if ($lastSeen === false || time() - $lastSeen >= 15) {
            return null;
        }

        return @file_get_contents($path) ?: null;
    }

    public function getLiveFrame(Request $request)
    {
        $frame = $this->latestFrame();
        $headers = [
            'Cache-Control' => 'no-store',
            'X-Raspi-Status' => $frame === null ? 'offline' : 'online',
        ];

        if ($frame === null) {
            return response()->json([
                'success' => false,
                'message' => 'Belum ada gambar terbaru dari Raspberry Pi.',
                'status' => 'offline',
            ], 200, $headers);
        }

        $response = response($frame, 200, $headers + [
            'Content-Type' => 'image/jpeg',
            'X-Content-Type-Options' => 'nosniff',
        ]);
        $response->setEtag(hash('sha256', $frame));
        $response->isNotModified($request);

        return $response;
    }

    public function getLiveStream()
    {
        if ($this->latestFrame() === null) {
            return response()->json(['error' => 'Kamera Raspberry Pi sedang offline.'], 503);
        }

        return response()->stream(function () {
            $previousFrame = null;
            for ($i = 0; $i < 150 && ! connection_aborted(); $i++) {
                $frame = $this->latestFrame();
                if ($frame === null) {
                    break;
                }
                if ($frame !== $previousFrame) {
                    echo "--frame\r\nContent-Type: image/jpeg\r\n\r\n".$frame."\r\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                    $previousFrame = $frame;
                }
                usleep(200000);
            }
        }, 200, [
            'Content-Type' => 'multipart/x-mixed-replace; boundary=frame',
            'Cache-Control' => 'no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}


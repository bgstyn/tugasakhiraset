<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ScanCheckHistory
{
    public const LIMIT = 100;

    public function record(array $asset, string $source, ?string $scanId = null, ?int $receivedAt = null): array
    {
        $scanId ??= (string) Str::uuid();

        return DB::transaction(function () use ($asset, $source, $scanId, $receivedAt) {
            DB::table('asset_scan_check_guard')->where('id', 1)->lockForUpdate()->firstOrFail();
            $checks = DB::table('asset_scan_checks');
            $existing = (clone $checks)->where('scan_id', $scanId)->lockForUpdate()->first();
            if ($existing) {
                return $this->serialize($existing);
            }
            $id = $checks->insertGetId([
                'scan_id' => $scanId,
                'source' => $source,
                'received_at' => $receivedAt ?? (int) (microtime(true) * 1000),
                'asset' => json_encode($asset, JSON_THROW_ON_ERROR),
            ]);
            // Current/locking reads also work inside an existing MySQL transaction.
            $expired = (clone $checks)->orderByDesc('id')->offset(self::LIMIT)
                ->limit(1000)->lockForUpdate()->pluck('id');
            if ($expired->isNotEmpty()) {
                (clone $checks)->whereIn('id', $expired)->delete();
            }

            return $this->serialize((clone $checks)->where('id', $id)->firstOrFail());
        }, 3);
    }

    public function since(int $cursor): array
    {
        return DB::table('asset_scan_checks')->where('id', '>', $cursor)
            ->orderByDesc('id')->limit(self::LIMIT)->get()
            ->map(fn ($row) => $this->serialize($row))->all();
    }

    private function serialize(object $row): array
    {
        return [
            'id' => (int) $row->id,
            'scan_id' => $row->scan_id,
            'source' => $row->source,
            'received_at' => (int) $row->received_at,
            'data' => json_decode($row->asset, true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}

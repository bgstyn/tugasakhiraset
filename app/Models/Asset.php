<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'asset_id',
        'government_inventory_number',
        'serial_number',
        'category',
        'brand',
        'model',
        'specification',
        'building',
        'floor',
        'room',
        'current_user',
        'year',
        'status',
        'rfid_uid',
        'rfid_status',
        'photo',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($asset) {
            if (empty($asset->asset_id)) {
                $year = $asset->year ?? date('Y');
                $prefix = "TIPNP-" . $year;
                
                // Retrieve the latest sequence for the given year
                $latestAsset = self::where('asset_id', 'like', $prefix . '-%')
                    ->orderBy('asset_id', 'desc')
                    ->first();
                
                $nextNum = 1;
                if ($latestAsset && preg_match('/-(\d+)$/', $latestAsset->asset_id, $matches)) {
                    $nextNum = (int)$matches[1] + 1;
                }
                
                $code = $prefix . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                while (self::where('asset_id', $code)->exists()) {
                    $nextNum++;
                    $code = $prefix . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
                }
                
                $asset->asset_id = $code;
            }
        });

        static::updating(function ($asset) {
            if ($asset->getOriginal('status') === 'write_off' && $asset->isDirty('status')) {
                throw new \Exception("Aset yang sudah Write Off tidak dapat diubah statusnya kembali.");
            }

            if ($asset->isDirty('status')) {
                $newStatus = $asset->status;
                if ($newStatus === 'standby' || $newStatus === 'rusak') {
                    $asset->current_user = null;
                    $asset->building = 'Gedung TI';
                    $asset->floor = '1';
                    $asset->room = 'Gudang Jurusan';
                } elseif ($newStatus === 'fraud') {
                    $asset->building = 'Gedung TI';
                    $asset->floor = '1';
                    $asset->room = 'Gudang Investigasi';
                } elseif ($newStatus === 'write_off') {
                    $asset->building = 'Gedung TI';
                    $asset->floor = '1';
                    $asset->room = 'Gudang Arsip';
                }
            }

            $staff = session('staff_it') ?? [
                'name' => 'System',
                'position' => 'System Observer',
                'location' => 'System',
            ];

            $dirty = $asset->getDirty();
            $loggedActions = [];
            foreach ($dirty as $field => $newValue) {
                $oldValue = $asset->getOriginal($field);
                if ($oldValue === $newValue) {
                    continue;
                }

                $action = null;
                if ($field === 'status') {
                    $action = 'status_change';
                } elseif (in_array($field, ['building', 'floor', 'room'])) {
                    $action = 'location_change';
                } elseif ($field === 'current_user') {
                    $action = 'assignment_change';
                } elseif ($field === 'rfid_uid') {
                    $action = 'rfid_change';
                }

                if ($action && !in_array($action, $loggedActions)) {
                    AssetHistory::create([
                        'asset_id' => $asset->id,
                        'asset_name' => $asset->name,
                        'action' => $action,
                        'changed_by_name' => $staff['name'],
                        'changed_by_position' => $staff['position'],
                        'changed_by_location' => $staff['location'] ?? 'E310',
                        'old_values' => [$field => $oldValue],
                        'new_values' => [$field => $newValue],
                    ]);
                    $loggedActions[] = $action;
                }
            }
        });

        static::deleting(function ($asset) {
            if ($asset->status === 'write_off') {
                throw new \Exception("Aset dengan status Write Off bersifat arsip permanen dan tidak boleh dihapus.");
            }
        });
    }

    public function publicQrUrl(): string
    {
        $path = route('assets.public.short-show', [
            'inventory_code' => rawurlencode($this->asset_id),
        ], false);

        return rtrim(config('app.url'), '/').$path;
    }

    public function getQrPngUrlAttribute(): string
    {
        return route('assets.qr', ['asset' => $this, 'format' => 'png'], false);
    }

    public function getQrSvgUrlAttribute(): string
    {
        return route('assets.qr', ['asset' => $this, 'format' => 'svg'], false);
    }

    /**
     * Get the histories for the asset.
     */
    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class);
    }

    /**
     * Get the approval requests for this asset.
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(AssetApproval::class);
    }

    /**
     * Get the bundles this asset belongs to.
     */
    public function bundles(): BelongsToMany
    {
        return $this->belongsToMany(AssetBundle::class, 'asset_bundle_items')
                    ->withTimestamps();
    }

    /**
     * Get the maintenance tickets for the asset.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(MaintenanceTicket::class, 'asset_id');
    }
}

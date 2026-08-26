<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AssetBundle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'location_id',
        'description',
    ];

    /**
     * Auto-generate bundle code on creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bundle) {
            if (empty($bundle->code)) {
                $latest = self::orderBy('id', 'desc')->first();
                $nextNum = $latest ? ((int) ltrim(str_replace('BDL-', '', $latest->code), '0') + 1) : 1;
                $bundle->code = 'BDL-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * The assets that belong to this bundle.
     */
    public function assets(): BelongsToMany
    {
        return $this->belongsToMany(Asset::class, 'asset_bundle_items')
                    ->withTimestamps();
    }

    /**
     * The location this bundle is primarily associated with.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Count assets in this bundle.
     */
    public function getAssetCountAttribute(): int
    {
        return $this->assets()->count();
    }
}

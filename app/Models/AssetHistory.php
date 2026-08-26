<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'asset_name',
        'action',
        'changed_by_name',
        'changed_by_position',
        'changed_by_location',
        'old_values',
        'new_values',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the asset that owns the history.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            throw new \Exception("Audit Trail cannot be modified.");
        });

        static::deleting(function ($model) {
            throw new \Exception("Audit Trail cannot be deleted.");
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}

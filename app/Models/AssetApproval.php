<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'asset_name',
        'type',
        'previous_status',
        'reason',
        'document_path',
        'notes',
        'requested_by_name',
        'requested_by_position',
        'status',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('status') && $model->getOriginal('status') !== 'pending') {
                throw new \Exception("Approval request has already been processed.");
            }
        });

        static::deleting(function ($model) {
            throw new \Exception("Approval request cannot be deleted.");
        });
    }

    /**
     * Get the asset that this approval belongs to.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Human-readable label for the approval type.
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'fraud'     => 'Fraud / Hilang',
            'write_off' => 'Write Off',
            default     => $this->type,
        };
    }

    /**
     * Human-readable label for the approval status.
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'  => 'Menunggu Persetujuan',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            default    => $this->status,
        };
    }
}

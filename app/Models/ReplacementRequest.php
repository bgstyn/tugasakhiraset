<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReplacementRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'ticket_id',
        'requested_by',
        'reason',
        'status',
        'resolved_by',
        'notes',
        'resolved_at',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            if ($model->isDirty('status') && $model->getOriginal('status') !== 'pending') {
                throw new \Exception("Replacement request has already been processed.");
            }
        });

        static::deleting(function ($model) {
            throw new \Exception("Replacement request cannot be deleted.");
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'ticket_id',
        'technician_id',
        'diagnosis',
        'cause',
        'action_taken',
        'spareparts',
        'photo_before',
        'photo_after',
        'notes',
        'maintenance_date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::updating(function ($model) {
            throw new \Exception("Maintenance Logbook entry cannot be modified.");
        });

        static::deleting(function ($model) {
            throw new \Exception("Maintenance Logbook entry cannot be deleted.");
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

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_number',
        'asset_id',
        'reporter_name',
        'reporter_contact',
        'description',
        'photo',
        'priority',
        'status',
        'assigned_to',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $dateStr = date('Ymd');
                $todayCount = static::whereDate('created_at', today())->count();
                $seq = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
                $number = 'TKT-' . $dateStr . '-' . $seq;

                while (static::where('ticket_number', $number)->exists()) {
                    $todayCount++;
                    $seq = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);
                    $number = 'TKT-' . $dateStr . '-' . $seq;
                }

                $ticket->ticket_number = $number;
            }
        });

        static::created(function ($ticket) {
            AssetHistory::create([
                'asset_id' => $ticket->asset_id,
                'asset_name' => $ticket->asset->name,
                'action' => 'ticket_created',
                'changed_by_name' => $ticket->reporter_name,
                'changed_by_position' => 'Pelapor Publik',
                'changed_by_location' => '-',
                'old_values' => null,
                'new_values' => [
                    'ticket_number' => $ticket->ticket_number,
                    'description' => $ticket->description,
                    'priority' => $ticket->priority,
                ],
            ]);
        });

        static::updated(function ($ticket) {
            if ($ticket->isDirty('status')) {
                $staff = session('staff_it') ?? [
                    'name' => 'System',
                    'position' => 'System Observer',
                    'location' => 'System',
                ];

                AssetHistory::create([
                    'asset_id' => $ticket->asset_id,
                    'asset_name' => $ticket->asset->name,
                    'action' => 'ticket_status_change',
                    'changed_by_name' => $staff['name'],
                    'changed_by_position' => $staff['position'],
                    'changed_by_location' => $staff['location'] ?? 'E310',
                    'old_values' => ['ticket_status' => $ticket->getOriginal('status')],
                    'new_values' => [
                        'ticket_number' => $ticket->ticket_number,
                        'ticket_status' => $ticket->status,
                    ],
                ]);
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }
}

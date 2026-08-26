<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_ruangan',
        'nama_ruangan',
        'lantai',
    ];

    /**
     * Get the floor and room name for display (e.g. "Lantai 1 - Lab Jaringan").
     */
    public function getFloorRoomNameAttribute(): string
    {
        return "Lantai {$this->lantai} - {$this->nama_ruangan}";
    }

    /**
     * Get the full location name (e.g. "Lantai 3 - E301 (Labor Pemrograman 1)").
     */
    public function getFullLocationAttribute(): string
    {
        return "Lantai {$this->lantai} - {$this->kode_ruangan} ({$this->nama_ruangan})";
    }

    /**
     * Get the assets for this location.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}

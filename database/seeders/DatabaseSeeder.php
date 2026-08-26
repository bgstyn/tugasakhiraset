<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed the default administrator account
        $this->call(AdminSeeder::class);

        // Seed some initial assets
        \App\Models\Asset::create([
            'name' => 'Monitor',
            'government_inventory_number' => '3.10.01.05.00001',
            'serial_number' => 'SNMON-0001',
            'current_user' => 'Reivan Hafillah',
            'year' => 2025,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E310',
            'status' => 'digunakan',
            'category' => 'Monitor',
        ]);

        \App\Models\Asset::create([
            'name' => 'Printer HP 310',
            'government_inventory_number' => '3.10.01.05.00002',
            'serial_number' => 'SNPRN-0001',
            'current_user' => 'Reivan Hafillah',
            'year' => 2025,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E310',
            'status' => 'standby',
            'category' => 'Monitor',
        ]);
    }
}

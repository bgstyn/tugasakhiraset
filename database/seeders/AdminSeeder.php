<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Seed the default administrator account.
     * Can be run independently: php artisan db:seed --class=AdminSeeder
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'teknologiinformasi'],
            [
                'name' => 'Administrator',
                'username' => 'teknologiinformasi',
                'email' => null,
                'password' => Hash::make('ti2026'),
                'role' => 'administrator',
                'position' => 'Administrator',
                'location' => null,
            ]
        );

        $this->command->info('Admin account created/updated: teknologiinformasi');
    }
}

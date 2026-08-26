<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ruangan')->unique();
            $table->string('nama_ruangan');
            $table->integer('lantai');
            $table->timestamps();
        });

        // Seed Jurusan Teknologi Informasi rooms
        $rooms = [
            ['kode_ruangan' => 'E301', 'nama_ruangan' => 'Labor Pemrograman 1', 'lantai' => 3],
            ['kode_ruangan' => 'E302', 'nama_ruangan' => 'Labor Pemrograman 2', 'lantai' => 3],
            ['kode_ruangan' => 'E306', 'nama_ruangan' => 'Labor Jaringan 2', 'lantai' => 3],
            ['kode_ruangan' => 'E308', 'nama_ruangan' => 'Labor Jaringan 1', 'lantai' => 3],
            ['kode_ruangan' => 'E310', 'nama_ruangan' => 'Labor Multimedia', 'lantai' => 3],
            ['kode_ruangan' => 'E311', 'nama_ruangan' => 'Labor Sistem Informasi', 'lantai' => 3],
            ['kode_ruangan' => 'E201', 'nama_ruangan' => 'Labor Internet of Things', 'lantai' => 2],
            ['kode_ruangan' => 'E202', 'nama_ruangan' => 'Labor Perakitan dan Instalasi', 'lantai' => 2],
            ['kode_ruangan' => 'E204', 'nama_ruangan' => 'Labor Artificial Intelligence', 'lantai' => 2],
            ['kode_ruangan' => 'E208', 'nama_ruangan' => 'Labor Sistem Informasi 2', 'lantai' => 2],
            ['kode_ruangan' => 'A402', 'nama_ruangan' => 'Labor Komputer', 'lantai' => 4],
            ['kode_ruangan' => 'A212', 'nama_ruangan' => 'Labor Multimedia (SBSN)', 'lantai' => 2],
            ['kode_ruangan' => 'Studio (SBSN)', 'nama_ruangan' => 'Studio (SBSN)', 'lantai' => 1],
            ['kode_ruangan' => 'E210A', 'nama_ruangan' => 'E210A', 'lantai' => 2],
            ['kode_ruangan' => 'E210B', 'nama_ruangan' => 'E210B', 'lantai' => 2],
            ['kode_ruangan' => 'C101A', 'nama_ruangan' => 'C101A', 'lantai' => 1],
            ['kode_ruangan' => 'C101B', 'nama_ruangan' => 'C101B', 'lantai' => 1],
            ['kode_ruangan' => 'C102A', 'nama_ruangan' => 'C102A', 'lantai' => 1],
            ['kode_ruangan' => 'C102B', 'nama_ruangan' => 'C102B', 'lantai' => 1],
            ['kode_ruangan' => 'F303', 'nama_ruangan' => 'F303', 'lantai' => 3],
            ['kode_ruangan' => 'F304', 'nama_ruangan' => 'F304', 'lantai' => 3],
            ['kode_ruangan' => 'E101', 'nama_ruangan' => 'E101', 'lantai' => 1],
            ['kode_ruangan' => 'E102', 'nama_ruangan' => 'E102', 'lantai' => 1],
            ['kode_ruangan' => 'E106', 'nama_ruangan' => 'E106', 'lantai' => 1],
            ['kode_ruangan' => 'E107', 'nama_ruangan' => 'E107', 'lantai' => 1],
            ['kode_ruangan' => 'E108', 'nama_ruangan' => 'E108', 'lantai' => 1],
        ];

        foreach ($rooms as $room) {
            DB::table('locations')->insert([
                'kode_ruangan' => $room['kode_ruangan'],
                'nama_ruangan' => $room['nama_ruangan'],
                'lantai' => $room['lantai'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};

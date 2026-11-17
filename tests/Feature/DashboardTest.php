<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function asset(array $overrides = []): Asset
    {
        return Asset::create(array_merge([
            'name' => 'Komputer Lab', 'government_inventory_number' => fake()->unique()->uuid(),
            'year' => 2026, 'building' => 'Gedung A', 'floor' => '1', 'room' => 'Lab 01',
            'status' => 'standby', 'category' => 'Komputer', 'rfid_status' => 'belum_terdaftar',
        ], $overrides));
    }

    public function test_dashboard_has_readable_empty_charts_and_requires_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertSee('Tren tiket perbaikan')->assertSee('Kondisi aset')->assertSee('Aset per ruangan')
            ->assertSee('Belum ada lokasi aset')->assertDontSee('NaN')->assertDontSee('Infinity')
            ->assertViewHas('ticketTrend', fn ($trend) => $trend->count() === 6 && $trend->sum('opened') === 0)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 0);
    }

    public function test_status_totals_include_pending_assets_and_rfid_counts_ignore_empty_uids(): void
    {
        $this->asset(['status' => 'digunakan', 'rfid_uid' => 'TAG-001', 'rfid_status' => 'aktif']);
        $this->asset(['status' => 'pending_fraud_approval', 'rfid_uid' => '']);
        $this->asset(['status' => 'pending_write_off_approval']);
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertViewHas('statusChart', fn ($chart) => $chart->sum('value') === 3 && $chart->firstWhere('key', 'pending_fraud_approval')['value'] === 1)
            ->assertViewHas('rfidStats', fn ($stats) => $stats['with_rfid'] === 1 && $stats['without_rfid'] === 2);
    }

    public function test_same_room_name_in_different_buildings_is_not_combined(): void
    {
        $this->asset(['building' => 'Gedung A']);
        $this->asset(['building' => 'Gedung B']);
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertViewHas('roomStats', fn ($rooms) => $rooms->count() === 2 && $rooms->every(fn ($room) => $room->assets_count === 1));
    }

    public function test_ticket_chart_uses_creation_and_completion_dates_across_years(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 17)->startOfDay());
        $asset = $this->asset();
        $old = DB::table('maintenance_tickets')->insertGetId([
            'asset_id' => $asset->id, 'ticket_number' => 'OLD', 'reporter_name' => 'Pelapor',
            'description' => 'Laporan lama', 'priority' => 'medium', 'status' => 'completed',
            'created_at' => '2025-07-10 12:00:00', 'updated_at' => '2026-01-16 12:00:00',
        ]);
        DB::table('maintenance_tickets')->insert([
            'asset_id' => $asset->id, 'ticket_number' => 'NEW', 'reporter_name' => 'Pelapor',
            'description' => 'Laporan baru', 'priority' => 'medium', 'status' => 'waiting_sparepart',
            'created_at' => '2026-01-05 12:00:00', 'updated_at' => '2026-01-05 12:00:00',
        ]);
        foreach (['2025-11-01 12:00:00', '2025-11-02 12:00:00'] as $date) {
            DB::table('ticket_histories')->insert(['ticket_id' => $old, 'new_status' => 'completed', 'created_at' => $date, 'updated_at' => $date]);
        }
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['open_tickets'] === 1)
            ->assertViewHas('ticketTrend', fn ($trend) => $trend->pluck('month')->all() === ['2025-08', '2025-09', '2025-10', '2025-11', '2025-12', '2026-01']
                && $trend->sum('opened') === 1 && $trend->firstWhere('month', '2025-11')['completed'] === 1 && $trend->last()['completed'] === 0);
    }
}

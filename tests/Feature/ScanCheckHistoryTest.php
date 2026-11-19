<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\User;
use App\Services\ScanCheckHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ScanCheckHistoryTest extends TestCase
{
    use RefreshDatabase;

    private function asset(): Asset
    {
        return Asset::create([
            'asset_id' => 'SCAN-001', 'name' => 'Komputer Laboratorium',
            'government_inventory_number' => 'INV-SCAN-001', 'year' => 2026,
            'status' => 'digunakan', 'current_user' => 'Tim Lab',
            'rfid_uid' => 'AABBCCDD', 'rfid_status' => 'aktif',
        ]);
    }

    public function test_history_is_shared_between_authenticated_staff_and_not_public(): void
    {
        $this->getJson(route('assets.scan.history'))->assertUnauthorized();
        $asset = $this->asset();
        $this->actingAs(User::factory()->create())
            ->postJson(route('assets.scan.lookup'), ['code' => $asset->asset_id])
            ->assertOk()->assertJsonPath('history.source', 'qr');
        $this->actingAs(User::factory()->create())
            ->getJson(route('assets.scan.history'))->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.data.asset_id', $asset->asset_id);
        $this->assertSame('digunakan', $asset->fresh()->status);
        $this->assertSame('Tim Lab', $asset->fresh()->current_user);
        $this->assertDatabaseCount('asset_histories', 0);
    }

    public function test_pi_scan_replay_is_recorded_once_and_history_keeps_only_the_latest_100(): void
    {
        $asset = $this->asset();
        $id = (string) Str::uuid();
        $headers = ['X-API-Token' => 'test-rfid-token', 'Idempotency-Key' => $id];
        $first = $this->postJson(route('api.rfid.scan'), ['rfid_uid' => $asset->rfid_uid], $headers)
            ->assertOk()->assertJsonPath('history.source', 'rfid')->json();
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => $asset->rfid_uid], $headers)
            ->assertOk()->assertExactJson($first);
        $this->assertDatabaseCount('asset_scan_checks', 1);

        AssetHistory::create([
            'asset_id' => $asset->id, 'asset_name' => $asset->name, 'action' => 'create',
            'changed_by_name' => 'Test', 'changed_by_position' => 'Staf', 'changed_by_location' => 'Lab',
        ]);
        $history = app(ScanCheckHistory::class);
        for ($i = 0; $i < 104; $i++) {
            $history->record($first['data'], 'qr');
        }
        $this->assertDatabaseCount('asset_scan_checks', 100);
        $this->assertDatabaseMissing('asset_scan_checks', ['scan_id' => $id]);
        $this->assertDatabaseCount('rfid_event_receipts', 1);
        $this->assertDatabaseCount('asset_histories', 1);
        $this->assertDatabaseCount('assets', 1);
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => $asset->rfid_uid], $headers)
            ->assertOk()->assertExactJson($first);
        $this->assertDatabaseCount('asset_scan_checks', 100);
        $this->assertSame(6, (int) DB::table('asset_scan_checks')->min('id'));
    }

    public function test_failed_checks_and_pairing_do_not_become_successful_check_history(): void
    {
        $asset = $this->asset();
        $headers = ['X-API-Token' => 'test-rfid-token'];
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => 'UNKNOWN'], $headers)->assertNotFound();
        $this->postJson(route('api.rfid.register'), ['asset_id' => $asset->asset_id, 'rfid_uid' => 'NEW-TAG'], $headers)->assertOk();
        $this->actingAs(User::factory()->create())->postJson(route('assets.scan.lookup'), ['code' => 'UNKNOWN'])->assertNotFound();
        $this->assertDatabaseCount('asset_scan_checks', 0);
    }

    public function test_service_deduplicates_scan_ids_and_cursor_returns_only_new_records(): void
    {
        $asset = $this->asset();
        $history = app(ScanCheckHistory::class);
        $id = (string) Str::uuid();
        $first = $history->record($asset->only(['id', 'name', 'asset_id']), 'qr', $id);
        $this->assertSame($first, $history->record($asset->only(['id', 'name', 'asset_id']), 'qr', $id));
        $second = $history->record($asset->only(['id', 'name', 'asset_id']), 'rfid');
        $this->actingAs(User::factory()->create())
            ->getJson(route('assets.scan.history', ['after_id' => $first['id']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('latest_id', $second['id']);
        $this->getJson(route('assets.scan.history', ['after_id' => $second['id']]))
            ->assertOk()->assertJsonCount(0, 'data')->assertJsonPath('latest_id', $second['id']);
        $this->getJson(route('assets.scan.history', ['after_id' => -1]))->assertUnprocessable();
    }

    public function test_legacy_numeric_qr_links_are_checked_without_navigating_away(): void
    {
        $asset = $this->asset();
        $this->actingAs(User::factory()->create())
            ->postJson(route('assets.scan.lookup'), ['code' => 'http://192.168.100.5/assets/'.$asset->id])
            ->assertOk()->assertJsonPath('history.data.id', $asset->id);
        $this->assertDatabaseCount('asset_scan_checks', 1);
    }
}

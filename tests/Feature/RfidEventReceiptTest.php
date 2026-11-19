<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfidEventReceiptTest extends TestCase
{
    use RefreshDatabase;

    private function asset(array $attributes = []): Asset
    {
        return Asset::create($attributes + [
            'asset_id' => 'TI-L1-Studio (SBSN)-0001',
            'government_inventory_number' => 'TEST-RFID-1',
            'name' => 'Test asset',
            'year' => 2026,
        ]);
    }

    private function headers(): array
    {
        return ['X-API-Token' => 'test-rfid-token', 'Idempotency-Key' => (string) Str::uuid()];
    }

    public function test_replayed_registration_returns_original_result_after_asset_is_remapped(): void
    {
        $asset = $this->asset();
        $headers = $this->headers();
        $payload = ['asset_id' => $asset->asset_id, 'rfid_uid' => 'AABB0011'];

        $first = $this->postJson(route('api.rfid.register'), $payload, $headers);
        $first->assertOk()->assertJsonPath('data.rfid_uid', 'AABB0011')->assertJsonPath('data.already_registered', false);
        $this->assertSame('AABB0011', $asset->fresh()->rfid_uid);
        $historyCount = AssetHistory::count();
        $this->assertGreaterThan(0, $historyCount);

        // Simulate a later administrative correction before the Pi receives its retry response.
        Asset::whereKey($asset->id)->update(['rfid_uid' => 'AABB0022']);
        $this->postJson(route('api.rfid.register'), array_reverse($payload, true), $headers)
            ->assertOk()->assertExactJson($first->json());
        $this->assertSame($historyCount, AssetHistory::count());
        $this->assertSame('AABB0022', $asset->fresh()->rfid_uid);
        $this->assertDatabaseCount('rfid_event_receipts', 1);

        $this->postJson(route('api.rfid.register'), ['rfid_uid' => 'AABB0033'] + $payload, $headers)
            ->assertConflict();
        $this->postJson(route('api.rfid.register'), $payload, $this->headers())
            ->assertConflict()->assertJsonPath('code', 'ASSET_ALREADY_PAIRED');
        $this->assertSame('AABB0022', $asset->fresh()->rfid_uid);
    }

    public function test_existing_mapping_from_before_the_receipt_migration_is_a_noop(): void
    {
        $asset = $this->asset(['rfid_uid' => 'AABB0011', 'rfid_status' => 'aktif']);

        $this->postJson(route('api.rfid.register'), [
            'asset_id' => $asset->asset_id,
            'rfid_uid' => $asset->rfid_uid,
        ], $this->headers())->assertOk()->assertJsonPath('data.rfid_uid', $asset->rfid_uid)->assertJsonPath('data.already_registered', true);

        $this->assertDatabaseCount('asset_histories', 0);
        $this->assertDatabaseCount('rfid_event_receipts', 1);
    }

    public function test_tag_on_another_asset_is_reported_without_changing_either_mapping(): void
    {
        $owner = $this->asset(['rfid_uid' => 'AABB0011', 'rfid_status' => 'aktif']);
        $target = $this->asset(['asset_id' => 'TARGET-002', 'government_inventory_number' => 'TEST-RFID-2']);
        $this->postJson(route('api.rfid.register'), [
            'asset_id' => $target->asset_id, 'rfid_uid' => $owner->rfid_uid,
        ], $this->headers())->assertConflict()->assertJsonPath('code', 'RFID_ALREADY_PAIRED');
        $this->assertSame('AABB0011', $owner->fresh()->rfid_uid);
        $this->assertNull($target->fresh()->rfid_uid);
        $this->assertDatabaseCount('asset_histories', 0);
    }

    public function test_replay_of_scan_cannot_overwrite_latest_scan_or_repeat_an_update(): void
    {
        $asset = $this->asset();
        $headers = $this->headers();
        $payload = [
            'code_asset' => 'https://public.example/a/TI-L1-Studio%20%28SBSN%29-0001',
            'status' => 'digunakan',
        ];
        $first = $this->postJson(route('api.rfid.scan'), $payload, $headers);
        $first->assertOk()->assertJsonPath('data.updated', true);
        $this->assertSame($first->json(), Cache::get('latest_asset_scan'));
        Cache::put('latest_asset_scan', ['timestamp' => 12345], 120);
        $historyCount = AssetHistory::count();
        Asset::whereKey($asset->id)->update(['status' => 'standby']);

        $this->postJson(route('api.rfid.scan'), $payload, $headers)
            ->assertOk()->assertExactJson($first->json());
        $this->assertSame('standby', $asset->fresh()->status);
        $this->assertSame($historyCount, AssetHistory::count());
        $this->assertSame(['timestamp' => 12345], Cache::get('latest_asset_scan'));
    }

    public function test_legacy_unauthenticated_and_invalid_key_requests_do_not_create_receipts(): void
    {
        $asset = $this->asset();
        $payload = ['asset_id' => $asset->asset_id];
        $this->postJson(route('api.rfid.scan'), $payload, ['X-API-Token' => 'test-rfid-token'])->assertOk();
        $this->postJson(route('api.rfid.scan'), $payload, ['Idempotency-Key' => (string) Str::uuid()])
            ->assertUnauthorized();
        $this->postJson(route('api.rfid.scan'), $payload, ['Idempotency-Key' => 'invalid'] + $this->headers())
            ->assertBadRequest();
        $this->assertDatabaseCount('rfid_event_receipts', 0);
    }

    public function test_failed_transaction_can_retry_with_the_same_key(): void
    {
        $asset = $this->asset(['status' => 'write_off']);
        $headers = $this->headers();
        $payload = ['asset_id' => $asset->asset_id, 'status' => 'digunakan'];

        $this->postJson(route('api.rfid.scan'), $payload, $headers)->assertInternalServerError();
        $this->assertDatabaseCount('rfid_event_receipts', 0);
        $this->assertDatabaseCount('asset_histories', 0);

        Asset::whereKey($asset->id)->update(['status' => 'standby']);
        $this->postJson(route('api.rfid.scan'), $payload, $headers)->assertOk();
        $this->assertDatabaseCount('rfid_event_receipts', 1);
    }

    public function test_existing_client_can_authenticate_with_the_configured_token(): void
    {
        $asset = $this->asset();
        config(['services.rfid.token' => 'configured-test-token']);

        $this->postJson(route('api.rfid.scan'), ['asset_id' => $asset->asset_id], [
            'X-API-Token' => 'configured-test-token',
        ])->assertOk();
    }
}

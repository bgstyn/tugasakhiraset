<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RfidActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_tags_cannot_validate_or_change_assets_but_qr_and_uid_capture_still_work(): void
    {
        $headers = ['X-API-Token' => 'test-rfid-token'];
        foreach (['nonaktif', 'belum_terdaftar'] as $index => $status) {
            $asset = Asset::create([
                'asset_id' => 'INACTIVE-'.$index, 'name' => 'Komputer', 'government_inventory_number' => 'INV-'.$index,
                'year' => 2026, 'status' => 'digunakan', 'rfid_uid' => 'TAG-'.$index, 'rfid_status' => $status,
            ]);
            $this->postJson(route('api.rfid.scan'), ['rfid_uid' => $asset->rfid_uid, 'status' => 'rusak'], $headers)
                ->assertConflict()->assertJsonPath('success', false)->assertJsonPath('code', 'RFID_INACTIVE');
            $this->postJson(route('api.rfid.validate'), ['rfid_uid' => $asset->rfid_uid], $headers)
                ->assertConflict()->assertJsonPath('success', false);
            $this->assertSame('digunakan', $asset->fresh()->status);
            $this->assertDatabaseCount('asset_histories', 0);
            $this->postJson(route('api.rfid.read'), ['rfid_uid' => $asset->rfid_uid, 'scan_id' => (string) Str::uuid()], $headers)->assertOk();
            $this->actingAs(User::factory()->create())->getJson(route('assets.rfid.validate', ['rfid_uid' => $asset->rfid_uid]))
                ->assertConflict()->assertJsonPath('code', 'RFID_INACTIVE');
            $this->postJson(route('assets.scan.lookup'), ['code' => $asset->asset_id])->assertOk()->assertJsonPath('data.id', $asset->id);
        }
    }

    public function test_rejected_scan_replays_consistently_and_reactivated_tag_accepts_a_new_scan(): void
    {
        $asset = Asset::create(['name' => 'Komputer', 'asset_id' => 'ACTIVE-1', 'government_inventory_number' => 'INV-1', 'year' => 2026, 'rfid_uid' => 'AA11', 'rfid_status' => 'nonaktif']);
        $id = (string) Str::uuid();
        $headers = ['X-API-Token' => 'test-rfid-token', 'Idempotency-Key' => $id];
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => 'AA11'], $headers)->assertConflict();
        $asset->update(['rfid_status' => 'aktif']);
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => 'AA11'], $headers)->assertConflict();
        $headers['Idempotency-Key'] = (string) Str::uuid();
        $this->postJson(route('api.rfid.scan'), ['rfid_uid' => 'AA11'], $headers)->assertOk()->assertJsonPath('success', true);
        $this->assertDatabaseCount('rfid_event_receipts', 2);
    }
}

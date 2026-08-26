<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RfidTest extends TestCase
{
    use RefreshDatabase;

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer test-rfid-token',
        ];
    }

    /**
     * Test lookup asset via RFID scan endpoint using rfid_uid.
     */
    public function test_can_lookup_asset_via_rfid_uid_scan(): void
    {
        $asset = Asset::create([
            'name' => 'PC Lab Core i7',
            'asset_id' => 'TIPNP-2026-0001',
            'government_inventory_number' => '3.10.01.05.00001',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'PC',
            'brand' => 'Custom',
            'model' => 'Intel Core i7 Gen 14',
            'rfid_uid' => 'RFID-TAG-12345',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->postJson(route('api.rfid.scan'), [
            'rfid_uid' => 'RFID-TAG-12345',
        ], $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Aset berhasil ditemukan',
            'data' => [
                'name' => 'PC Lab Core i7',
                'asset_id' => 'TIPNP-2026-0001',
                'rfid_uid' => 'RFID-TAG-12345',
                'status' => 'standby',
            ]
        ]);
    }

    /**
     * Test lookup fails for unregistered RFID tag.
     */
    public function test_lookup_fails_for_unregistered_rfid(): void
    {
        $response = $this->postJson(route('api.rfid.scan'), [
            'rfid_uid' => 'RFID-UNKNOWN-99999',
        ], $this->getHeaders());

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => "Aset dengan RFID UID 'RFID-UNKNOWN-99999' belum terdaftar."
        ]);
    }

    /**
     * Test lookup can also update status and location.
     */
    public function test_lookup_can_update_status_and_location(): void
    {
        $asset = Asset::create([
            'name' => 'Server Dell PowerEdge',
            'asset_id' => 'TIPNP-2026-0002',
            'government_inventory_number' => '3.10.01.05.00002',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Server',
            'brand' => 'Dell',
            'model' => 'R760',
            'rfid_uid' => 'RFID-TAG-SERVER',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->postJson(route('api.rfid.scan'), [
            'rfid_uid' => 'RFID-TAG-SERVER',
            'status' => 'digunakan',
            'building' => 'Gedung Elektro',
            'floor' => '2',
            'room' => 'L02',
            'current_user' => 'Admin TI',
        ], $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Aset berhasil diperbarui via RFID',
            'data' => [
                'name' => 'Server Dell PowerEdge',
                'status' => 'digunakan',
                'building' => 'Gedung Elektro',
                'floor' => '2',
                'room' => 'L02',
                'current_user' => 'Admin TI',
                'updated' => true
            ]
        ]);

        // Check DB
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'status' => 'digunakan',
            'building' => 'Gedung Elektro',
            'floor' => '2',
            'room' => 'L02',
            'current_user' => 'Admin TI',
        ]);
    }

    /**
     * Test registering RFID UID to an asset.
     */
    public function test_can_register_rfid_uid_to_asset(): void
    {
        $asset = Asset::create([
            'name' => 'Monitor Asus 24',
            'asset_id' => 'TIPNP-2026-0003',
            'government_inventory_number' => '3.10.01.05.00003',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Monitor',
            'brand' => 'Asus',
            'model' => 'VZ249HE',
            'rfid_uid' => null,
            'rfid_status' => 'belum_terdaftar',
        ]);

        $response = $this->postJson(route('api.rfid.register'), [
            'asset_id' => 'TIPNP-2026-0003',
            'rfid_uid' => 'RFID-NEW-TAG-007',
        ], $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'RFID UID berhasil didaftarkan ke aset',
            'data' => [
                'name' => 'Monitor Asus 24',
                'asset_id' => 'TIPNP-2026-0003',
                'rfid_uid' => 'RFID-NEW-TAG-007',
                'rfid_status' => 'aktif',
            ]
        ]);

        // Verify DB
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'rfid_uid' => 'RFID-NEW-TAG-007',
            'rfid_status' => 'aktif',
        ]);
    }

    /**
     * Test duplicate RFID tag registration fails.
     */
    public function test_duplicate_rfid_registration_fails(): void
    {
        Asset::create([
            'name' => 'Asset 1',
            'asset_id' => 'TIPNP-2026-0004',
            'government_inventory_number' => '3.10.01.05.00004',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Laptop',
            'brand' => 'Lenovo',
            'model' => 'ThinkPad',
            'rfid_uid' => 'RFID-TAG-EXISTING',
            'rfid_status' => 'aktif',
        ]);

        Asset::create([
            'name' => 'Asset 2',
            'asset_id' => 'TIPNP-2026-0005',
            'government_inventory_number' => '3.10.01.05.00005',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Laptop',
            'brand' => 'HP',
            'model' => 'EliteBook',
            'rfid_uid' => null,
            'rfid_status' => 'belum_terdaftar',
        ]);

        $response = $this->postJson(route('api.rfid.register'), [
            'asset_id' => 'TIPNP-2026-0005',
            'rfid_uid' => 'RFID-TAG-EXISTING',
        ], $this->getHeaders());

        $response->assertStatus(400);
        $response->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'rfid_uid'
            ]
        ]);
    }

    /**
     * Test validate RFID endpoint.
     */
    public function test_api_can_validate_registered_rfid(): void
    {
        $asset = Asset::create([
            'name' => 'Tablet Samsung',
            'asset_id' => 'TIPNP-2026-0006',
            'government_inventory_number' => '3.10.01.05.00006',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Tablet',
            'brand' => 'Samsung',
            'model' => 'Galaxy Tab',
            'rfid_uid' => 'RFID-VALID-TAG',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->postJson(route('api.rfid.validate'), [
            'rfid_uid' => 'RFID-VALID-TAG',
        ], $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'RFID terdaftar.',
            'data' => [
                'asset_id' => 'TIPNP-2026-0006',
                'rfid_uid' => 'RFID-VALID-TAG',
                'rfid_status' => 'aktif',
            ]
        ]);
    }

    /**
     * Test get asset details by RFID endpoint.
     */
    public function test_api_can_retrieve_asset_details_by_rfid(): void
    {
        $asset = Asset::create([
            'name' => 'Tablet iPad',
            'asset_id' => 'TIPNP-2026-0007',
            'government_inventory_number' => '3.10.01.05.00007',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Tablet',
            'brand' => 'Apple',
            'model' => 'iPad Air',
            'rfid_uid' => 'RFID-IPAD-TAG',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->getJson(route('api.rfid.asset') . '?rfid_uid=RFID-IPAD-TAG', $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'asset_id' => 'TIPNP-2026-0007',
                'rfid_uid' => 'RFID-IPAD-TAG',
                'brand' => 'Apple',
                'model' => 'iPad Air',
            ]
        ]);
    }

    /**
     * Test sync mappings endpoint.
     */
    public function test_api_can_sync_rfid_mappings(): void
    {
        Asset::create([
            'name' => 'Asset 1',
            'asset_id' => 'TIPNP-2026-0008',
            'government_inventory_number' => '3.10.01.05.00008',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Laptop',
            'rfid_uid' => 'RFID-SYNC-1',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->getJson(route('api.rfid.sync'), $this->getHeaders());

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'asset_id',
                    'rfid_uid',
                    'rfid_status',
                ]
            ]
        ]);
    }

    /**
     * Test API rejects unauthorized token.
     */
    public function test_api_rejects_unauthorized_token(): void
    {
        $response = $this->postJson(route('api.rfid.scan'), [
            'rfid_uid' => 'RFID-TAG-12345',
        ], [
            'Authorization' => 'Bearer WRONG-TOKEN',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized RFID API request.'
        ]);
    }

    /**
     * Test Web register RFID UID.
     */
    public function test_web_user_can_register_rfid(): void
    {
        $user = User::factory()->create(['role' => 'teknisi']);
        $asset = Asset::create([
            'name' => 'Monitor Dell',
            'asset_id' => 'TIPNP-2026-0009',
            'government_inventory_number' => '3.10.01.05.00009',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Monitor',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['staff_it' => [
                'name' => $user->name,
                'position' => 'Administrator',
                'location' => 'E310',
            ]])
            ->post(route('assets.rfid.register-web', $asset->id), [
                'rfid_uid' => 'WEB-RFID-TAG-123',
            ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'rfid_uid' => 'WEB-RFID-TAG-123',
            'rfid_status' => 'aktif',
        ]);
    }

    /**
     * Test Web toggle RFID UID active state.
     */
    public function test_web_user_can_toggle_rfid_status(): void
    {
        $user = User::factory()->create(['role' => 'teknisi']);
        $asset = Asset::create([
            'name' => 'Monitor Dell',
            'asset_id' => 'TIPNP-2026-0010',
            'government_inventory_number' => '3.10.01.05.0010',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Monitor',
            'rfid_uid' => 'RFID-TO-TOGGLE',
            'rfid_status' => 'aktif',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['staff_it' => [
                'name' => $user->name,
                'position' => 'Administrator',
                'location' => 'E310',
            ]])
            ->post(route('assets.rfid.toggle-web', $asset->id));

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'rfid_status' => 'nonaktif',
        ]);
    }

    /**
     * Test Web delete RFID UID.
     */
    public function test_admin_can_delete_rfid_but_teknisi_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $teknisi = User::factory()->create(['role' => 'teknisi']);
        
        $asset = Asset::create([
            'name' => 'Monitor Dell',
            'asset_id' => 'TIPNP-2026-0011',
            'government_inventory_number' => '3.10.01.05.0011',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'Monitor',
            'rfid_uid' => 'RFID-TO-DELETE',
            'rfid_status' => 'aktif',
        ]);

        // 1. Teknisi attempts deletion -> blocks/errors
        $response1 = $this->actingAs($teknisi)
            ->withSession(['staff_it' => [
                'name' => $teknisi->name,
                'position' => 'Technician',
                'location' => 'E310',
            ]])
            ->post(route('assets.rfid.delete-web', $asset->id));

        $response1->assertStatus(302);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'rfid_uid' => 'RFID-TO-DELETE',
        ]);

        // 2. Admin attempts deletion -> succeeds
        $response2 = $this->actingAs($admin)
            ->withSession(['staff_it' => [
                'name' => $admin->name,
                'position' => 'Admin Boss',
                'location' => 'E310',
            ]])
            ->post(route('assets.rfid.delete-web', $asset->id));

        $response2->assertStatus(302);
        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'rfid_uid' => null,
            'rfid_status' => 'belum_terdaftar',
        ]);
    }
}

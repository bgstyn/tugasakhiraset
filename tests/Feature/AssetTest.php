<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use App\Models\AssetHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an authenticated user with staff_it session data
        $this->user = User::factory()->create([
            'username' => 'testteknisi',
            'name' => 'John Doe IT',
            'role' => 'teknisi',
            'position' => 'Administrator',
            'location' => 'E310',
        ]);
    }

    private function getAuthenticated($uri, $method = 'get', $data = [])
    {
        return $this->actingAs($this->user)
            ->withSession(['staff_it' => [
                'name' => $this->user->name,
                'position' => $this->user->position,
                'location' => $this->user->location,
            ]])
            ->call($method, $uri, $data);
    }

    public function test_can_view_assets_list_page_when_authenticated(): void
    {
        $response = $this->getAuthenticated(route('assets.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_new_asset_with_full_specifications(): void
    {
        $assetData = [
            'name' => 'MacBook Pro M3 Max',
            'government_inventory_number' => '3.10.01.05.00001',
            'current_user' => 'Jane Smith',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E310',
            'status' => 'digunakan',
            'category' => 'Laptop',
            'brand' => 'Apple',
            'model' => 'MacBook Pro 16-inch 2026',
            'specification' => 'Apple M3 Max 16-core CPU, 48 GB Unified Memory, 1 TB SSD',
            'serial_number' => 'C02XXXXX',
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['staff_it' => [
                'name' => $this->user->name,
                'position' => $this->user->position,
                'location' => $this->user->location,
            ]])
            ->post(route('assets.store'), $assetData);

        $response->assertStatus(302);
        $response->assertRedirect(route('assets.index'));

        // Check if database contains the asset
        $this->assertDatabaseHas('assets', [
            'name' => 'MacBook Pro M3 Max',
            'asset_id' => 'TIPNP-2026-0001', // Auto-generated internal ID
            'government_inventory_number' => '3.10.01.05.00001',
            'category' => 'Laptop',
            'brand' => 'Apple',
            'model' => 'MacBook Pro 16-inch 2026',
            'specification' => 'Apple M3 Max 16-core CPU, 48 GB Unified Memory, 1 TB SSD',
            'serial_number' => 'C02XXXXX',
        ]);

        // Check if asset history log was created
        $asset = Asset::where('asset_id', 'TIPNP-2026-0001')->firstOrFail();
        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'action' => 'create',
            'changed_by_name' => 'John Doe IT',
            'changed_by_position' => 'Administrator',
            'changed_by_location' => 'E310',
        ]);
    }

    public function test_can_update_asset_specifications(): void
    {
        $asset = Asset::create([
            'name' => 'Dell Latitude 5430',
            'asset_id' => 'TIPNP-2025-0001',
            'government_inventory_number' => '3.10.01.05.00002',
            'serial_number' => 'SN-DELL-OLD',
            'current_user' => 'Bob',
            'year' => 2025,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E310',
            'status' => 'standby',
            'category' => 'Laptop',
            'brand' => 'Dell',
            'model' => 'Latitude 5430',
            'specification' => 'Intel Core i5-1235U, 8 GB DDR4, 256 GB SSD',
        ]);

        $updatedData = [
            'name' => 'Dell Latitude 5430 Upgraded',
            'government_inventory_number' => '3.10.01.05.00002',
            'serial_number' => 'SN-DELL-NEW', // updated SN
            'current_user' => 'Bob',
            'year' => 2025,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E310',
            'status' => 'digunakan',
            'category' => 'Laptop',
            'brand' => 'Dell',
            'model' => 'Latitude 5430 Upgraded',
            'specification' => 'Intel Core i5-1235U, 16 GB DDR4, 512 GB SSD', // upgraded specs
        ];

        $response = $this->actingAs($this->user)
            ->withSession(['staff_it' => [
                'name' => $this->user->name,
                'position' => $this->user->position,
                'location' => $this->user->location,
            ]])
            ->put(route('assets.update', $asset->id), $updatedData);

        $response->assertStatus(302);
        $response->assertRedirect(route('assets.show', $asset->id));

        $this->assertDatabaseHas('assets', [
            'id' => $asset->id,
            'name' => 'Dell Latitude 5430 Upgraded',
            'specification' => 'Intel Core i5-1235U, 16 GB DDR4, 512 GB SSD',
            'serial_number' => 'SN-DELL-NEW',
        ]);

        // Check if history records the update
        $this->assertDatabaseHas('asset_histories', [
            'asset_id' => $asset->id,
            'action' => 'update',
            'changed_by_name' => 'John Doe IT',
        ]);
    }
}

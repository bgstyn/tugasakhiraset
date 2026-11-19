<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetBundle;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_workspace_has_both_methods_and_one_sidebar_entry(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get(route('assets.scan'))->assertOk()->assertViewIs('assets.scan')
            ->assertSee('QR / Kamera')->assertSee('Tag RFID')->assertDontSee('Periksa tag RFID');
        $page = $this->get(route('assets.scan', ['mode' => 'rfid']))->assertOk()
            ->assertViewIs('assets.rfid.validate')->assertSee('rfid-live-badge')
            ->assertDontSee('Pembaca RFID')->assertDontSee('Hasil validasi')
            ->assertDontSee('rfid-result-heading')->assertSee('id="rfid-inline-result"', false);
        $this->assertSame(1, substr_count($page->getContent(), 'aria-label="Cek aset"'));
    }

    public function test_old_rfid_page_redirects_but_json_validation_still_works(): void
    {
        $asset = Asset::create([
            'asset_id' => 'CHECK-001', 'name' => 'Komputer', 'government_inventory_number' => 'INV-CHECK-001',
            'year' => 2026, 'status' => 'standby', 'rfid_uid' => 'A1B2', 'rfid_status' => 'aktif',
        ]);
        $this->actingAs(User::factory()->create())->get(route('assets.rfid.validate'))
            ->assertRedirect(route('assets.scan', ['mode' => 'rfid']));
        $this->getJson(route('assets.rfid.validate', ['rfid_uid' => 'A1B2']))
            ->assertOk()->assertJsonPath('data.id', $asset->id);
    }

    public function test_disabled_bundles_hide_menu_and_block_crud_without_removing_data(): void
    {
        config(['features.asset_bundles' => false]);
        $bundle = AssetBundle::create(['name' => 'Paket tersimpan']);
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()->assertDontSee('Paket aset');
        foreach (['index', 'create', 'show', 'edit'] as $action) {
            $this->get(route('bundles.'.$action, in_array($action, ['show', 'edit']) ? $bundle : []))->assertNotFound();
        }
        $this->post(route('bundles.store'), ['name' => 'Baru'])->assertNotFound();
        $this->patch(route('bundles.update', $bundle), ['name' => 'Diubah'])->assertNotFound();
        $this->delete(route('bundles.destroy', $bundle))->assertNotFound();
        $this->assertDatabaseCount('asset_bundles', 1);
        $this->assertSame('Paket tersimpan', $bundle->fresh()->name);
    }

    public function test_bundles_can_be_enabled_again_without_recreating_the_data(): void
    {
        $bundle = AssetBundle::create(['name' => 'Paket tersimpan']);
        config(['features.asset_bundles' => true]);
        $this->actingAs(User::factory()->create())->get(route('bundles.index'))
            ->assertOk()->assertSee($bundle->name)->assertSee('Paket aset');
    }
}

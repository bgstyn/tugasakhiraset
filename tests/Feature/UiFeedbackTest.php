<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\ReplacementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_use_a_single_shared_notification(): void
    {
        $user = User::factory()->create(['name' => 'Staf Contoh']);
        $this->post(route('login'), ['username' => $user->username, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $page = $this->get(route('dashboard'))->assertOk()->assertSee('data-toast', false);
        $this->assertSame(1, substr_count($page->getContent(), 'Selamat datang, Staf Contoh!'));
        $page->assertDontSee('class="ops-flash"', false);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk()->assertSee('data-toast', false)
            ->assertSee('Anda telah berhasil logout.')->assertDontSee('class="ops-flash"', false);
    }

    public function test_asset_actions_reflect_rfid_and_replacement_states(): void
    {
        $user = User::factory()->create();
        $asset = Asset::create([
            'asset_id' => 'STATE-001', 'name' => 'Komputer', 'government_inventory_number' => 'INV-STATE-001',
            'year' => 2026, 'status' => 'maintenance', 'rfid_uid' => 'ABCDEF', 'rfid_status' => 'aktif',
        ]);
        $this->actingAs($user)->get(route('assets.show', $asset))->assertOk()->assertSee('RFID terhubung')->assertSee('Ajukan Ganti');
        $asset->update(['rfid_status' => 'nonaktif']);
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('RFID nonaktif');
        $asset->update(['rfid_uid' => null]);
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('Hubungkan RFID');
        $request = ReplacementRequest::create([
            'asset_id' => $asset->id, 'requested_by' => $user->id,
            'reason' => 'Tidak dapat diperbaiki lagi.', 'status' => 'pending',
        ]);
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('Penggantian menunggu persetujuan');
        $request->update(['status' => 'approved']);
        $this->get(route('assets.show', $asset))->assertOk()->assertSee('Penggantian disetujui');
    }
}

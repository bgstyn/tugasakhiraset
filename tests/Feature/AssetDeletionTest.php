<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MaintenanceTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssetDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function asset(string $status = 'standby'): Asset
    {
        return Asset::create([
            'asset_id' => 'DELETE-TEST-001', 'government_inventory_number' => 'INV-DELETE-001',
            'name' => 'Aset pengujian hapus', 'year' => 2026, 'status' => $status,
        ]);
    }

    public function test_deletion_preserves_audit_and_uses_nullable_staff_defaults(): void
    {
        $user = User::factory()->create(['position' => null, 'location' => null]);
        $asset = $this->asset();
        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id, 'reporter_name' => 'Pelapor', 'description' => 'Rusak', 'priority' => 'low',
        ]);
        $this->actingAs($user)->withSession(['staff_it' => [
            'name' => $user->name, 'position' => null, 'location' => null,
        ]])->delete(route('assets.destroy', $asset))
            ->assertRedirect(route('assets.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('maintenance_tickets', ['id' => $ticket->id]);
        $this->assertDatabaseHas('asset_histories', ['asset_name' => $asset->name, 'action' => 'delete', 'asset_id' => null]);
        $this->assertDatabaseHas('asset_histories', ['asset_name' => $asset->name, 'action' => 'ticket_created', 'asset_id' => null]);
    }

    public function test_write_off_remains_an_archive_and_returns_useful_feedback(): void
    {
        $asset = $this->asset('write_off');
        $this->actingAs(User::factory()->create())->from(route('assets.show', $asset))
            ->delete(route('assets.destroy', $asset))->assertRedirect(route('assets.show', $asset))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('assets', ['id' => $asset->id, 'status' => 'write_off']);
        $this->assertDatabaseMissing('asset_histories', ['action' => 'delete']);
        $this->get(route('assets.index'))->assertOk()->assertDontSee('title="Hapus Aset"', false);
        $this->get(route('assets.show', $asset))->assertOk()->assertDontSee('value="DELETE"', false);
    }

    public function test_audit_failure_rolls_back_deletion_and_related_tickets(): void
    {
        $asset = $this->asset();
        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id, 'reporter_name' => 'Pelapor', 'description' => 'Rusak', 'priority' => 'low',
        ]);
        Event::listen('eloquent.creating: '.AssetHistory::class, function ($history) {
            if ($history->action === 'delete') {
                throw new \RuntimeException('Simulated audit write failure');
            }
        });
        $this->actingAs(User::factory()->create())->delete(route('assets.destroy', $asset))->assertStatus(500);
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        $this->assertDatabaseHas('maintenance_tickets', ['id' => $ticket->id, 'asset_id' => $asset->id]);
        $this->assertDatabaseHas('asset_histories', ['asset_id' => $asset->id, 'action' => 'ticket_created']);
    }
}

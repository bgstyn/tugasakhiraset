<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetApproval;
use App\Models\ReplacementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplacementRequestTest extends TestCase
{
    use RefreshDatabase;

    private function asset(): Asset
    {
        return Asset::create([
            'asset_id' => 'REPLACEMENT-001', 'name' => 'Komputer Pengujian',
            'government_inventory_number' => 'INV-REPLACEMENT-001',
            'year' => 2026, 'building' => 'Gedung TI', 'floor' => '1',
            'room' => 'Lab', 'status' => 'maintenance',
        ]);
    }

    public function test_valid_submission_reaches_admin_list_and_navigation_badge(): void
    {
        $asset = $this->asset();
        $technician = User::factory()->create();
        $reason = 'Mainboard terbakar dan tidak dapat diperbaiki.';

        $this->actingAs($technician)->from(route('assets.show', $asset))
            ->post(route('assets.replacement.store', $asset), ['reason' => $reason])
            ->assertRedirect(route('assets.show', $asset))->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $replacement = ReplacementRequest::sole();
        $this->assertSame($technician->id, $replacement->requested_by);
        $this->assertSame('pending', $replacement->status);
        $this->assertSame('maintenance', $asset->fresh()->status);
        $this->get(route('assets.show', $asset))->assertOk()
            ->assertSee('Penggantian menunggu persetujuan');

        $admin = User::factory()->create(['role' => 'administrator']);
        $this->actingAs($admin)->get(route('admin.approvals.index'))
            ->assertOk()->assertViewHas('pendingCount', 1)
            ->assertViewHas('replacements', fn ($requests) => $requests->contains('id', $replacement->id))
            ->assertSee($reason)->assertSee('aria-label="1 menunggu"', false);
    }

    public function test_navigation_counts_both_status_and_replacement_requests(): void
    {
        $asset = $this->asset();
        $admin = User::factory()->create(['role' => 'administrator']);
        ReplacementRequest::create([
            'asset_id' => $asset->id, 'requested_by' => $admin->id,
            'reason' => 'Perlu penggantian perangkat.', 'status' => 'pending',
        ]);
        AssetApproval::create([
            'asset_id' => $asset->id, 'asset_name' => $asset->name,
            'type' => 'fraud', 'previous_status' => 'maintenance',
            'reason' => 'Pemeriksaan aset diperlukan.', 'requested_by_name' => $admin->name,
            'requested_by_position' => 'Administrator', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('admin.approvals.index'))
            ->assertOk()->assertViewHas('pendingCount', 2)
            ->assertSee('aria-label="2 menunggu"', false);
    }

    public function test_short_reason_returns_visible_error_and_preserves_input_in_replacement_form(): void
    {
        $asset = $this->asset();
        $this->actingAs(User::factory()->create())->from(route('assets.show', $asset))
            ->post(route('assets.replacement.store', $asset), ['reason' => 'rusak'])
            ->assertRedirect(route('assets.show', $asset))
            ->assertSessionHasErrorsIn('replacement', ['reason']);
        $this->assertDatabaseCount('replacement_requests', 0);

        $this->get(route('assets.show', $asset))->assertOk()
            ->assertSee('Alasan penggantian minimal 10 karakter.')
            ->assertSee('minlength="10"', false)->assertSee('maxlength="1000"', false)
            ->assertSee('>rusak</textarea>', false)
            ->assertSee('openRequestReplacementModal();', false);
    }

    public function test_a_second_pending_request_does_not_create_a_duplicate(): void
    {
        $asset = $this->asset();
        $this->actingAs(User::factory()->create());
        $url = route('assets.replacement.store', $asset);
        $this->post($url, ['reason' => 'Mainboard tidak bisa diperbaiki.'])->assertSessionHas('success');
        $this->post($url, ['reason' => 'Mainboard tidak bisa diperbaiki.'])->assertSessionHas('error');
        $this->assertDatabaseCount('replacement_requests', 1);
    }

    public function test_only_authenticated_staff_can_submit_and_only_admin_can_resolve(): void
    {
        $asset = $this->asset();
        $this->post(route('assets.replacement.store', $asset), ['reason' => 'Perangkat perlu diganti.'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('replacement_requests', 0);
        $user = User::factory()->create();
        $replacement = ReplacementRequest::create([
            'asset_id' => $asset->id, 'requested_by' => $user->id,
            'reason' => 'Perangkat perlu diganti.', 'status' => 'pending',
        ]);
        $this->actingAs($user)->get(route('admin.approvals.index'))->assertForbidden();
        $this->patch(route('admin.replacements.approve', $replacement))->assertForbidden();
        $this->patch(route('admin.replacements.reject', $replacement), ['notes' => 'Masih bisa diperbaiki.'])->assertForbidden();
        $this->assertSame('pending', $replacement->fresh()->status);
    }

    public function test_admin_can_approve_and_processed_request_leaves_pending_list(): void
    {
        $asset = $this->asset();
        $admin = User::factory()->create(['role' => 'administrator']);
        $replacement = ReplacementRequest::create([
            'asset_id' => $asset->id, 'requested_by' => $admin->id,
            'reason' => 'Perangkat perlu diganti.', 'status' => 'pending',
        ]);
        $this->actingAs($admin)->patch(route('admin.replacements.approve', $replacement))
            ->assertSessionHas('success');
        $replacement->refresh();
        $this->assertSame('approved', $replacement->status);
        $this->assertSame($admin->id, $replacement->resolved_by);
        $this->assertSame('rusak', $asset->fresh()->status);
        $this->get(route('admin.approvals.index'))->assertOk()
            ->assertViewHas('pendingCount', 0)
            ->assertViewHas('replacements', fn ($requests) => $requests->isEmpty());
        $this->get(route('admin.approvals.index', ['filter' => 'all']))->assertOk()
            ->assertViewHas('replacements', fn ($requests) => $requests->contains('id', $replacement->id));
    }
}

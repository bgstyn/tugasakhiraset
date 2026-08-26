<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\TicketComment;
use App\Models\TicketHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test QR public information screen is readable without auth.
     */
    public function test_public_short_url_renders_asset_details_without_auth(): void
    {
        $asset = Asset::create([
            'name' => 'Proyektor Epson L510',
            'asset_id' => 'TIPNP-2026-0001',
            'government_inventory_number' => '3.10.01.05.00001',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '2',
            'room' => 'L02',
            'status' => 'standby',
            'category' => 'Proyektor',
        ]);

        $response = $this->get(route('assets.public.short-show', 'TIPNP-2026-0001'));

        $response->assertStatus(200);
        $response->assertViewIs('assets.public_show');
        $response->assertSee('Proyektor Epson L510');
        $response->assertSee('3.10.01.05.00001');
    }

    /**
     * Test damage ticket creation.
     */
    public function test_can_submit_damage_ticket_publicly(): void
    {
        Storage::fake('public');

        $asset = Asset::create([
            'name' => 'PC Lab Core i7',
            'asset_id' => 'TIPNP-2026-0002',
            'government_inventory_number' => '3.10.01.05.00002',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'PC',
        ]);

        $file = UploadedFile::fake()->create('broken_pc.jpg', 100);

        $response = $this->post(route('tickets.public.store', $asset->id), [
            'reporter_name' => 'Budi Santoso',
            'reporter_contact' => 'budi@pnp.ac.id',
            'description' => 'Komputer mati total saat dinyalakan.',
            'priority' => 'high',
            'photo' => $file,
        ]);

        $ticket = MaintenanceTicket::first();
        $this->assertNotNull($ticket);

        $response->assertRedirect(route('tickets.public.success', $ticket->ticket_number));

        $this->assertDatabaseHas('maintenance_tickets', [
            'asset_id' => $asset->id,
            'reporter_name' => 'Budi Santoso',
            'reporter_contact' => 'budi@pnp.ac.id',
            'description' => 'Komputer mati total saat dinyalakan.',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'new_status' => 'open',
        ]);
    }

    /**
     * Test tickets dashboard listing and authorization.
     */
    public function test_unauthenticated_cannot_access_tickets_dashboard(): void
    {
        $response = $this->get(route('tickets.index'));
        $response->assertRedirect('/login');
    }

    public function test_authenticated_can_access_tickets_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'teknisi']);
        $response = $this->actingAs($user)
            ->withSession(['staff_it' => [
                'name' => $user->name,
                'position' => 'Staff',
                'location' => 'E310',
            ]])
            ->get(route('tickets.index'));

        $response->assertStatus(200);
        $response->assertViewIs('tickets.index');
    }

    /**
     * Test concurrency-safe ticket claim logic.
     */
    public function test_technician_can_claim_ticket_and_concurrency_blocks_double_claim(): void
    {
        $asset = Asset::create([
            'name' => 'PC Lab Core i7',
            'asset_id' => 'TIPNP-2026-0003',
            'government_inventory_number' => '3.10.01.05.00003',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'PC',
        ]);

        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id,
            'reporter_name' => 'Budi',
            'description' => 'Keyboard rusak.',
            'priority' => 'low',
            'status' => 'open',
        ]);

        $tech1 = User::factory()->create(['role' => 'teknisi']);
        $tech2 = User::factory()->create(['role' => 'teknisi']);

        // 1. Tech 1 claims ticket
        $response1 = $this->actingAs($tech1)
            ->withSession(['staff_it' => [
                'name' => $tech1->name,
                'position' => 'Staff 1',
                'location' => 'E310',
            ]])
            ->post(route('tickets.claim', $ticket->id));

        $response1->assertStatus(302);
        
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'assigned_to' => $tech1->id,
            'status' => 'assigned',
        ]);

        // 2. Tech 2 attempts claim -> fails/redirects back with error flash
        $response2 = $this->actingAs($tech2)
            ->withSession(['staff_it' => [
                'name' => $tech2->name,
                'position' => 'Staff 2',
                'location' => 'E310',
            ]])
            ->post(route('tickets.claim', $ticket->id));

        $response2->assertStatus(302);
        $response2->assertSessionHas('error', 'Gagal klaim: Tiket ini sudah diambil atau ditugaskan ke teknisi lain.');
        
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'assigned_to' => $tech1->id, // Keeps tech 1 assignment
        ]);
    }

    /**
     * Test admin can assign ticket.
     */
    public function test_admin_can_assign_ticket_to_technician(): void
    {
        $admin = User::factory()->create(['role' => 'administrator']);
        $tech = User::factory()->create(['role' => 'teknisi']);

        $asset = Asset::create([
            'name' => 'PC Lab Core i7',
            'asset_id' => 'TIPNP-2026-0004',
            'government_inventory_number' => '3.10.01.05.00004',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'PC',
        ]);

        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id,
            'reporter_name' => 'Budi',
            'description' => 'Layar berkedip.',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['staff_it' => [
                'name' => $admin->name,
                'position' => 'Admin Boss',
                'location' => 'E310',
            ]])
            ->post(route('tickets.assign', $ticket->id), [
                'assigned_to' => $tech->id,
            ]);

        $response->assertStatus(302);
        
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'assigned_to' => $tech->id,
            'status' => 'assigned',
        ]);
    }

    /**
     * Test status updates and timeline records.
     */
    public function test_assigned_tech_can_update_status_and_add_comment(): void
    {
        $tech = User::factory()->create(['role' => 'teknisi']);
        $asset = Asset::create([
            'name' => 'PC Lab Core i7',
            'asset_id' => 'TIPNP-2026-0005',
            'government_inventory_number' => '3.10.01.05.00005',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '1',
            'room' => 'L01',
            'status' => 'standby',
            'category' => 'PC',
        ]);

        $ticket = MaintenanceTicket::create([
            'asset_id' => $asset->id,
            'reporter_name' => 'Budi',
            'description' => 'Mati total.',
            'priority' => 'high',
            'status' => 'assigned',
            'assigned_to' => $tech->id,
        ]);

        // 1. Tech updates status
        $response1 = $this->actingAs($tech)
            ->withSession(['staff_it' => [
                'name' => $tech->name,
                'position' => 'Tech',
                'location' => 'E310',
            ]])
            ->post(route('tickets.updateStatus', $ticket->id), [
                'status' => 'in_progress',
                'comment' => 'Sedang membongkar power supply.',
            ]);

        $response1->assertStatus(302);
        
        $this->assertDatabaseHas('maintenance_tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseHas('ticket_histories', [
            'ticket_id' => $ticket->id,
            'old_status' => 'assigned',
            'new_status' => 'in_progress',
            'comment' => 'Sedang membongkar power supply.',
        ]);

        // 2. Tech adds comment
        $response2 = $this->actingAs($tech)
            ->withSession(['staff_it' => [
                'name' => $tech->name,
                'position' => 'Tech',
                'location' => 'E310',
            ]])
            ->post(route('tickets.storeComment', $ticket->id), [
                'content' => 'Butuh kapasitor pengganti 470uF.',
            ]);

        $response2->assertStatus(302);

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $tech->id,
            'content' => 'Butuh kapasitor pengganti 470uF.',
        ]);
    }
}

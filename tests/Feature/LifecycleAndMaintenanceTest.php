<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetHistory;
use App\Models\MaintenanceLog;
use App\Models\MaintenanceTicket;
use App\Models\ReplacementRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleAndMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teknisi;
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name' => 'Admin User',
            'username' => 'admin_test',
            'role' => 'administrator',
            'password' => bcrypt('password'),
        ]);

        $this->teknisi = User::create([
            'name' => 'Teknisi User',
            'username' => 'teknisi_test',
            'role' => 'teknisi',
            'password' => bcrypt('password'),
        ]);

        $this->asset = Asset::create([
            'name' => 'Server Dell PowerEdge',
            'asset_id' => 'TIPNP-2026-0001',
            'government_inventory_number' => '3.10.01.05.00001',
            'year' => 2026,
            'building' => 'Gedung TI',
            'floor' => '3',
            'room' => 'E301',
            'current_user' => 'Reivan Hafillah',
            'status' => 'digunakan',
            'category' => 'Server',
        ]);
    }

    /**
     * Test standby / rusak triggers automatic assignment end and location move.
     */
    public function test_automated_workflow_standby_or_rusak(): void
    {
        // 1. Standby
        $this->asset->status = 'standby';
        $this->asset->save();

        $this->assertNull($this->asset->current_user);
        $this->assertEquals('Gedung TI', $this->asset->building);
        $this->assertEquals('1', $this->asset->floor);
        $this->assertEquals('Gudang Jurusan', $this->asset->room);

        // Check history log
        $history = AssetHistory::where('asset_id', $this->asset->id)
            ->where('action', 'status_change')
            ->first();
        $this->assertNotNull($history);

        // 2. Rusak
        $this->asset->current_user = 'Another User';
        $this->asset->status = 'rusak';
        $this->asset->save();

        $this->assertNull($this->asset->current_user);
        $this->assertEquals('Gedung TI', $this->asset->building);
        $this->assertEquals('1', $this->asset->floor);
        $this->assertEquals('Gudang Jurusan', $this->asset->room);
    }

    /**
     * Test fraud moves location to Gudang Investigasi.
     */
    public function test_automated_workflow_fraud(): void
    {
        $this->asset->status = 'fraud';
        $this->asset->save();

        $this->assertEquals('Gedung TI', $this->asset->building);
        $this->assertEquals('1', $this->asset->floor);
        $this->assertEquals('Gudang Investigasi', $this->asset->room);
    }

    /**
     * Test write_off moves location to Gudang Arsip and prevents database deletion.
     */
    public function test_automated_workflow_write_off(): void
    {
        $this->asset->status = 'write_off';
        $this->asset->save();

        $this->assertEquals('Gedung TI', $this->asset->building);
        $this->assertEquals('1', $this->asset->floor);
        $this->assertEquals('Gudang Arsip', $this->asset->room);

        // Assert status is locked (cannot change status back)
        $this->expectException(\Exception::class);
        $this->asset->status = 'standby';
        $this->asset->save();
    }

    public function test_write_off_asset_cannot_be_deleted(): void
    {
        $this->asset->status = 'write_off';
        $this->asset->save();

        $this->expectException(\Exception::class);
        $this->asset->delete();
    }

    /**
     * Test maintenance logbook is created when completing ticket.
     */
    public function test_maintenance_logbook_creation_via_ticket(): void
    {
        $ticket = MaintenanceTicket::create([
            'asset_id' => $this->asset->id,
            'reporter_name' => 'John Doe',
            'description' => 'Monitor berkedip terus menerus',
            'priority' => 'medium',
            'status' => 'assigned',
            'assigned_to' => $this->teknisi->id,
        ]);

        $response = $this->actingAs($this->teknisi)
            ->post(route('tickets.updateStatus', $ticket->id), [
                'status' => 'completed',
                'comment' => 'Selesai disolder ulang kapasitor monitor.',
                'diagnosis' => 'Kapasitor power supply kembung',
                'cause' => 'Panas berlebih',
                'action_taken' => 'Solder ulang dan ganti kapasitor 470uF',
                'spareparts' => 'Kapasitor 470uF 25V',
                'maintenance_date' => date('Y-m-d'),
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Check MaintenanceLog record was created
        $log = MaintenanceLog::where('ticket_id', $ticket->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Kapasitor power supply kembung', $log->diagnosis);
        $this->assertEquals('Kapasitor 470uF 25V', $log->spareparts);

        // Check asset status automatically updated to standby (and moved to Gudang Jurusan)
        $this->asset->refresh();
        $this->assertEquals('standby', $this->asset->status);
        $this->assertNull($this->asset->current_user);
        $this->assertEquals('Gudang Jurusan', $this->asset->room);
    }

    /**
     * Test manually recording maintenance log.
     */
    public function test_manual_maintenance_logbook_creation(): void
    {
        $response = $this->actingAs($this->teknisi)
            ->post(route('assets.maintenance.store', $this->asset->id), [
                'diagnosis' => 'Debu tebal di kipas processor',
                'cause' => 'Jarang dibersihkan di Lab',
                'action_taken' => 'Pembersihan dengan blower dan ganti thermal paste',
                'spareparts' => 'Thermal paste Arctic MX-4',
                'maintenance_date' => date('Y-m-d'),
                'change_status_to_standby' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $log = MaintenanceLog::where('asset_id', $this->asset->id)->first();
        $this->assertNotNull($log);
        $this->assertEquals('Debu tebal di kipas processor', $log->diagnosis);

        $this->asset->refresh();
        $this->assertEquals('standby', $this->asset->status);
    }

    /**
     * Test replacement request submission and review flow.
     */
    public function test_replacement_request_flow(): void
    {
        // 1. Submit Request (Teknisi)
        $response = $this->actingAs($this->teknisi)
            ->post(route('assets.replacement.store', $this->asset->id), [
                'reason' => 'Aset rusak parah tersambar petir pada bagian ethernet port terintegrasi.',
            ]);

        $response->assertSessionHasNoErrors();
        
        $req = ReplacementRequest::where('asset_id', $this->asset->id)->first();
        $this->assertNotNull($req);
        $this->assertEquals('pending', $req->status);

        // 2. Admin Approve
        $response = $this->actingAs($this->admin)
            ->patch(route('admin.replacements.approve', $req->id), [
                'notes' => 'Disetujui. Pengadaan baru dilakukan semester depan.',
            ]);

        $response->assertSessionHasNoErrors();
        
        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals('Disetujui. Pengadaan baru dilakukan semester depan.', $req->notes);

        // Associated asset status should become 'rusak'
        $this->asset->refresh();
        $this->assertEquals('rusak', $this->asset->status);
        // And automatically moved to Gudang Jurusan due to 'rusak' status workflow!
        $this->assertEquals('Gudang Jurusan', $this->asset->room);
    }
}

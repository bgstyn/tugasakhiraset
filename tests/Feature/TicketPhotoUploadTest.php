<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\MaintenanceTicket;
use App\Models\TicketHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $public = sys_get_temp_dir().'/ticket-photo-test-'.bin2hex(random_bytes(8));
        File::makeDirectory($public);
        $this->app->usePublicPath($public);
        $this->beforeApplicationDestroyed(function () use ($public) {
            $resolved = realpath($public);
            $temporaryRoot = realpath(sys_get_temp_dir()).DIRECTORY_SEPARATOR;
            if ($resolved !== false && str_starts_with($resolved, $temporaryRoot.'ticket-photo-test-')) {
                File::deleteDirectory($resolved);
            }
        });
    }

    private function asset(): Asset
    {
        return Asset::create([
            'asset_id' => 'PHOTO-TEST-001', 'government_inventory_number' => 'INV-PHOTO-001',
            'name' => 'Aset uji foto', 'year' => 2026, 'status' => 'standby',
        ]);
    }

    private function payload(array $extra = []): array
    {
        return $extra + ['reporter_name' => 'Pelapor uji', 'description' => 'Layar mati saat digunakan.', 'priority' => 'high'];
    }

    public function test_photo_is_saved_to_writable_storage_and_contact_is_optional(): void
    {
        $asset = $this->asset();
        $image = UploadedFile::fake()->image('kerusakan.png', 120, 80);
        $response = $this->post(route('tickets.public.store', $asset), $this->payload(['photo' => $image]));
        $response->assertSessionHasNoErrors();
        $ticket = MaintenanceTicket::sole();
        $response->assertRedirect(route('tickets.public.success', $ticket->ticket_number));
        $this->assertNull($ticket->reporter_contact);
        $this->assertStringStartsWith('storage/tickets/', $ticket->photo);
        Storage::disk('public')->assertExists(substr($ticket->photo, strlen('storage/')));
        $this->assertDatabaseHas('ticket_histories', ['ticket_id' => $ticket->id, 'new_status' => 'open']);
    }

    public function test_submission_without_a_photo_or_contact_still_works(): void
    {
        $this->post(route('tickets.public.store', $this->asset()), $this->payload())->assertRedirect();
        $this->assertNull(MaintenanceTicket::sole()->photo);
        $this->assertNull(MaintenanceTicket::sole()->reporter_contact);
    }

    public function test_history_failure_rolls_back_ticket_and_removes_its_uploaded_photo(): void
    {
        Event::listen('eloquent.creating: '.TicketHistory::class, fn () => throw new \RuntimeException('Simulated ticket history failure'));
        $this->post(route('tickets.public.store', $this->asset()), $this->payload([
            'reporter_contact' => '', 'photo' => UploadedFile::fake()->image('gagal.jpg'),
        ]))->assertStatus(500);
        $this->assertDatabaseCount('maintenance_tickets', 0);
        $this->assertDatabaseCount('ticket_histories', 0);
        $this->assertDatabaseMissing('asset_histories', ['action' => 'ticket_created']);
        $this->assertSame([], Storage::disk('public')->allFiles('tickets'));
    }

    public function test_invalid_or_oversized_photo_is_rejected_without_creating_a_ticket(): void
    {
        $asset = $this->asset();
        foreach ([UploadedFile::fake()->create('document.pdf', 20, 'application/pdf'), UploadedFile::fake()->image('besar.jpg')->size(5121)] as $image) {
            $this->postJson(route('tickets.public.store', $asset), $this->payload(['photo' => $image]))
                ->assertUnprocessable()->assertJsonValidationErrors('photo');
        }
        $this->assertDatabaseCount('maintenance_tickets', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_storage_failure_returns_photo_feedback_instead_of_a_partial_ticket(): void
    {
        $asset = $this->asset();
        $disk = \Mockery::mock(\Illuminate\Filesystem\FilesystemAdapter::class);
        $disk->shouldReceive('putFile')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->postJson(route('tickets.public.store', $asset), $this->payload([
            'photo' => UploadedFile::fake()->image('kerusakan.png'),
        ]))->assertUnprocessable()->assertJsonValidationErrors('photo');
        $this->assertDatabaseCount('maintenance_tickets', 0);
        $this->assertDatabaseCount('ticket_histories', 0);
    }
}

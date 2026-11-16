<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class RaspiStreamTest extends TestCase
{
    use RefreshDatabase;

    public function test_camera_accepts_authenticated_jpegs_and_serves_fresh_frames_to_staff(): void
    {
        $originalStorage = storage_path();
        $temporaryStorage = sys_get_temp_dir().'/raspi-stream-'.Str::uuid();
        $this->app->useStoragePath($temporaryStorage);

        try {
            $token = ['Accept' => 'application/json', 'X-API-Token' => 'test-rfid-token'];
            $jpeg = UploadedFile::fake()->image('camera.jpg', 64, 48);
            $this->post(route('api.raspi.frame'), ['frame' => $jpeg], ['Accept' => 'application/json'])
                ->assertUnauthorized();
            $this->post(route('api.raspi.frame'), ['frame' => UploadedFile::fake()->image('camera.png')], $token)
                ->assertUnprocessable();
            $this->post(route('api.raspi.frame'), ['frame' => $jpeg], $token)
                ->assertOk()->assertJson(['success' => true]);

            $this->get(route('assets.scan.live-frame'))->assertRedirect(route('login'));
            $this->actingAs(User::factory()->create())
                ->get(route('assets.scan.live-frame'))
                ->assertOk()->assertHeader('Content-Type', 'image/jpeg')->assertHeader('X-Raspi-Status', 'online');
            $this->assertSame(file_get_contents($jpeg->getRealPath()), File::get(storage_path('app/raspi_frame.jpg')));

            touch(storage_path('app/raspi_frame.jpg'), time() - 20);
            $this->get(route('assets.scan.live-frame'))
                ->assertOk()->assertHeader('X-Raspi-Status', 'offline')->assertJsonPath('status', 'offline');
            $this->get(route('assets.scan.proxy-stream'))->assertServiceUnavailable();
        } finally {
            $this->app->useStoragePath($originalStorage);
            File::deleteDirectory($temporaryStorage);
        }
    }

    public function test_rfid_check_and_registration_are_visible_on_the_staff_feed(): void
    {
        $asset = Asset::create([
            'asset_id' => 'TI-TEST-0001',
            'government_inventory_number' => 'TEST-STREAM-1',
            'name' => 'Monitor Lab',
            'year' => 2026,
        ]);
        $registerId = (string) Str::uuid();
        $this->postJson(route('api.rfid.register'), [
            'asset_id' => $asset->asset_id,
            'rfid_uid' => 'A1B2C3D4',
        ], ['X-API-Token' => 'test-rfid-token', 'Idempotency-Key' => $registerId])->assertOk();

        $this->getJson(route('api.rfid.latest'))->assertUnauthorized();
        $this->getJson(route('api.rfid.latest'), ['X-API-Token' => 'test-rfid-token'])
            ->assertOk()->assertJsonPath('data.scan_id', $registerId);

        $this->actingAs(User::factory()->create());
        $this->getJson(route('assets.scan.latest'))
            ->assertOk()->assertJsonPath('data.scan_id', $registerId)
            ->assertJsonPath('data.source', 'rfid')->assertJsonPath('data.data.id', $asset->id);

        $check = $this->postJson(route('api.rfid.scan'), ['rfid_uid' => 'A1B2C3D4'])->assertOk();
        $this->assertNotSame($registerId, $check->json('scan_id'));
        $this->getJson(route('assets.scan.latest'))->assertOk()
            ->assertJsonPath('data.scan_id', $check->json('scan_id'))
            ->assertJsonPath('data.data.name', 'Monitor Lab');
        $this->getJson(route('assets.rfid.validate'))->assertUnprocessable();
        $this->getJson(route('assets.rfid.validate', ['rfid_uid' => 'A1B2C3D4']))
            ->assertOk()->assertJsonPath('data.id', $asset->id);
    }
}

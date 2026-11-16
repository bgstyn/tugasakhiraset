<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class AssetQrCodeTest extends TestCase
{
    use RefreshDatabase;

    private const CODE = 'TI-L1-Studio (SBSN)-0001';

    private const URL = 'https://assets.example.test/a/TI-L1-Studio%20%28SBSN%29-0001';

    private function asset(): Asset
    {
        return Asset::create([
            'asset_id' => self::CODE,
            'government_inventory_number' => 'TEST-QR-1',
            'name' => 'Studio QR',
            'year' => 2026,
        ]);
    }

    public function test_generated_png_uses_app_url_with_a_different_request_host(): void
    {
        config(['app.url' => 'https://assets.example.test/']);
        $asset = $this->asset();
        $this->assertSame(self::URL, $asset->publicQrUrl());
        $this->actingAs(User::factory()->create());

        $response = $this->get('http://192.168.1.50:8000'.$asset->qr_png_url);
        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $expected = (new PngWriter())->write(QrCode::create(self::URL)->setSize(440)->setMargin(30));
        $this->assertSame($expected->getString(), $response->getContent());
        $this->get($asset->qr_svg_url)->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
    }

    public function test_legacy_code_with_spaces_has_a_public_ticket_form(): void
    {
        config(['app.url' => 'https://assets.example.test']);
        $asset = $this->asset();
        $form = route('tickets.public.create', $asset->id, false);

        $this->get($asset->publicQrUrl())->assertOk()->assertViewIs('assets.public_show')->assertSee($form);
        $this->get('https://assets.example.test'.$form)->assertOk()->assertViewIs('tickets.create');
    }

    public function test_export_previews_first_and_writes_to_a_separate_batch(): void
    {
        $asset = $this->asset();
        $original = storage_path();
        $temporary = sys_get_temp_dir().'/asset-qr-test-'.Str::uuid();
        $this->app->useStoragePath($temporary);
        $root = $temporary.'/app/private/qr-exports';
        try {
            config(['app.url' => 'https://192.168.1.50']);
            $this->artisan('assets:export-qr', ['--write' => true])->assertFailed();
            config(['app.url' => 'https://assets.example.test']);
            $this->artisan('assets:export-qr')->assertSuccessful();
            $this->assertDirectoryDoesNotExist($root);
            $this->artisan('assets:export-qr', ['--write' => true, '--asset' => [self::CODE]])->assertSuccessful();

            [$batch] = File::directories($root);
            $this->assertStringContainsString(self::URL, File::get($batch.'/assets.csv'));
            $this->assertSame(IMAGETYPE_PNG, getimagesize($batch."/{$asset->id}.png")[2]);
            $this->assertStringContainsString('<svg', File::get($batch."/{$asset->id}.svg"));
        } finally {
            $this->app->useStoragePath($original);
            File::deleteDirectory($temporary);
        }
    }
}

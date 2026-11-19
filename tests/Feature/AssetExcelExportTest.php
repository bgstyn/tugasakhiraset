<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OpenSpout\Reader\XLSX\Reader;
use Tests\TestCase;
use ZipArchive;

class AssetExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private function asset(array $attributes = []): Asset
    {
        static $number = 0;
        ++$number;

        return Asset::create($attributes + [
            'asset_id' => 'EXCEL-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
            'government_inventory_number' => 'INV-XLSX-'.$number,
            'name' => 'Komputer '.$number, 'year' => 2026, 'status' => 'standby',
        ]);
    }

    private function download(array $query = []): array
    {
        $response = $this->get(route('assets.export', $query))->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
        $path = $response->baseResponse->getFile()->getPathname();
        $this->beforeApplicationDestroyed(fn () => is_file($path) && unlink($path));
        $reader = new Reader();
        $reader->open($path);
        try {
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                $this->assertSame('Daftar Aset', $sheet->getName());
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
            }
        } finally {
            $reader->close();
        }

        return [$path, array_values(array_filter($rows, fn ($row) => is_int($row[0] ?? null) || is_float($row[0] ?? null)))];
    }

    public function test_download_requires_login_and_the_button_preserves_filters(): void
    {
        $this->get(route('assets.export'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        $page = $this->get(route('assets.index', ['search' => 'Monitor', 'status' => 'rusak', 'sort_by' => 'nama_asc', 'page' => 2]));
        $page->assertOk()->assertSee('Unduh Excel')->assertDontSee('Unduh CSV');
        $page->assertSee(route('assets.export', ['search' => 'Monitor', 'status' => 'rusak', 'sort_by' => 'nama_asc']));
    }

    public function test_xlsx_has_readable_styles_panes_filter_and_typed_safe_values(): void
    {
        $this->actingAs(User::factory()->create());
        $asset = $this->asset([
            'asset_id' => '0001234567890123456789', 'government_inventory_number' => '00000017',
            'serial_number' => '00123456789012345678', 'rfid_uid' => '00004567', 'rfid_status' => 'aktif',
            'name' => '=HYPERLINK("https://example.com", "click")',
            'specification' => 'RAM 16 GB & SSD <512 GB>', 'status' => 'write_off',
        ]);
        [$path, $rows] = $this->download();
        $this->assertCount(1, $rows);
        $this->assertSame($asset->asset_id, $rows[0][1]);
        $this->assertSame('00000017', $rows[0][2]);
        $this->assertSame('00123456789012345678', $rows[0][3]);
        $this->assertSame($asset->name, $rows[0][4]);
        $this->assertSame('RAM 16 GB & SSD <512 GB>', $rows[0][8]);
        $this->assertEquals(2026, $rows[0][13]);
        $this->assertSame('Write off', $rows[0][14]);
        $this->assertSame('00004567', $rows[0][15]);
        $this->assertInstanceOf(\DateTimeInterface::class, $rows[0][17]);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open($path));
        try {
            $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
            $styles = $zip->getFromName('xl/styles.xml');
            $this->assertStringContainsString('autoFilter ref="A5:S6"', $xml);
            $this->assertStringContainsString('ySplit="5"', $xml);
            $this->assertStringContainsString('xSplit="2"', $xml);
            $this->assertStringContainsString('showGridLines="false"', $xml);
            $this->assertStringContainsString('width="34"', $xml);
            $this->assertStringNotContainsString('<f', $xml);
            $this->assertStringContainsString('1D4F91', $styles);
            $this->assertStringContainsString('dd/mm/yyyy hh:mm', $styles);
        } finally {
            $zip->close();
        }
        $this->assertDatabaseCount('assets', 1);
    }

    public function test_export_includes_all_filtered_rows_and_follows_list_sort_and_location(): void
    {
        $this->actingAs(User::factory()->create());
        $location = Location::create(['kode_ruangan' => 'LAB-EXCEL', 'lantai' => '2', 'nama_ruangan' => 'Lab Excel']);
        for ($index = 0; $index < 12; ++$index) {
            $this->asset(['name' => sprintf('Monitor %02d', $index), 'room' => 'LAB-EXCEL', 'floor' => '2']);
        }
        $this->asset(['name' => 'Monitor rusak', 'status' => 'rusak', 'room' => 'LAB-EXCEL', 'floor' => '2']);
        $this->asset(['name' => 'Monitor lantai lain', 'room' => 'LAB-EXCEL', 'floor' => '1']);
        $this->asset(['name' => 'Printer', 'room' => 'LAB-EXCEL', 'floor' => '2']);
        [$path, $rows] = $this->download(['search' => 'Monitor', 'status' => 'standby', 'location_id' => $location->id, 'sort_by' => 'nama_desc', 'page' => 2]);
        $this->assertCount(12, $rows);
        $this->assertSame('Monitor 11', $rows[0][4]);
        $this->assertSame('Monitor 00', $rows[11][4]);
        $zip = new ZipArchive();
        $zip->open($path);
        try {
            $sheet = new \DOMDocument();
            $sheet->loadXML($zip->getFromName('xl/worksheets/sheet1.xml'));
            $styles = new \DOMDocument();
            $styles->loadXML($zip->getFromName('xl/styles.xml'));
            $xpath = new \DOMXPath($sheet);
            $xpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $styleXpath = new \DOMXPath($styles);
            $styleXpath->registerNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $fillId = function ($cell) use ($xpath, $styleXpath) {
                $styleIndex = (int) $xpath->evaluate('string(//s:c[@r="'.$cell.'"]/@s)');

                return $styleXpath->evaluate('string(//s:cellXfs/s:xf['.($styleIndex + 1).']/@fillId)');
            };
            // Banded formatting must cover text and date cells, not dates alone.
            $this->assertSame($fillId('B7'), $fillId('R7'));
            $this->assertNotSame($fillId('B6'), $fillId('B7'));
        } finally {
            $zip->close();
        }
    }

    public function test_quick_filter_and_no_matches_produce_valid_workbooks(): void
    {
        $this->actingAs(User::factory()->create());
        $this->asset(['rfid_uid' => 'AA1122', 'rfid_status' => 'aktif']);
        $unlinked = $this->asset();
        [, $rows] = $this->download(['quick_filter' => 'no_rfid']);
        $this->assertCount(1, $rows);
        $this->assertSame($unlinked->asset_id, $rows[0][1]);
        $this->assertSame('Belum terhubung', $rows[0][16]);
        [, $empty] = $this->download(['search' => 'TIDAK-ADA-ASET-INI']);
        $this->assertSame([], $empty);
    }

    public function test_export_crosses_batch_boundary_without_losing_or_repeating_rows(): void
    {
        $this->actingAs(User::factory()->create());
        $records = [];
        for ($number = 0; $number < 501; ++$number) {
            $records[] = [
                'asset_id' => sprintf('BATCH-%04d', $number), 'government_inventory_number' => 'BATCH-INV-'.$number,
                'name' => 'Komputer '.$number, 'year' => 2026, 'status' => 'standby',
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        Asset::insert($records);
        [, $rows] = $this->download(['sort_by' => 'inv_code']);
        $codes = array_column($rows, 1);
        $this->assertCount(501, $codes);
        $this->assertCount(501, array_unique($codes));
        $this->assertSame('BATCH-0000', $codes[0]);
        $this->assertSame('BATCH-0500', $codes[500]);
    }

    public function test_failed_generation_does_not_leave_a_partial_download_file(): void
    {
        $this->actingAs(User::factory()->create());
        $path = null;
        $this->mock(\App\Services\AssetExcelExport::class, function ($mock) use (&$path) {
            $mock->shouldReceive('write')->once()->andReturnUsing(function ($query, $temporary) use (&$path) {
                $path = $temporary;
                file_put_contents($path, 'partial-file');
                throw new \RuntimeException('Export interrupted');
            });
        });
        $this->get(route('assets.export'))->assertStatus(500);
        $this->assertNotNull($path);
        $this->assertFileDoesNotExist($path);
    }
}

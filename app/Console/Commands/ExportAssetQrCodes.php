<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\AssetQrCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class ExportAssetQrCodes extends Command
{
    protected $signature = 'assets:export-qr {--asset=* : Filter by asset code} {--write : Generate a new export batch}';

    protected $description = 'Preview or export replacement QR labels using APP_URL';

    public function handle(AssetQrCode $qrCode): int
    {
        $url = parse_url(config('app.url'));
        $host = strtolower(rtrim($url['host'] ?? '', '.'));
        if (($url['scheme'] ?? '') !== 'https' || ! str_contains($host, '.')
            || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
            || Str::endsWith($host, ['.localhost', '.local', '.internal'])
            || array_intersect(['user', 'pass', 'query', 'fragment'], array_keys($url ?: []))) {
            $this->error('Set APP_URL to the public HTTPS domain and refresh the configuration cache.');

            return self::FAILURE;
        }

        $codes = $this->option('asset');
        $assets = Asset::query()->when($codes, fn ($query) => $query->whereIn('asset_id', $codes));
        if ($codes && count(array_diff($codes, $assets->pluck('asset_id')->all())) > 0) {
            $this->error('One or more requested asset codes do not exist.');

            return self::FAILURE;
        }

        $this->info($assets->count().' asset(s) using '.config('app.url'));
        if (! $this->option('write')) {
            return self::SUCCESS;
        }

        $directory = storage_path('app/private/qr-exports/'.Str::uuid());
        File::ensureDirectoryExists($directory, 0750);
        $manifest = fopen($directory.'/assets.partial.csv', 'xb');
        if ($manifest === false) {
            throw new RuntimeException('Unable to create the export manifest.');
        }

        try {
            if (fputcsv($manifest, ['id', 'asset_code', 'qr_url', 'png', 'svg'], escape: '') === false) {
                throw new RuntimeException('Unable to write the export manifest.');
            }
            foreach ($assets->lazyById() as $asset) {
                $png = "{$asset->id}.png";
                $svg = "{$asset->id}.svg";
                foreach (['png' => $png, 'svg' => $svg] as $format => $file) {
                    $content = $qrCode->render($asset, $format)->getString();
                    if (File::put($directory.'/'.$file, $content) !== strlen($content)) {
                        throw new RuntimeException("Unable to write {$file}.");
                    }
                }
                if (fputcsv($manifest, [$asset->id, $asset->asset_id, $asset->publicQrUrl(), $png, $svg], escape: '') === false) {
                    throw new RuntimeException('Unable to write the export manifest.');
                }
            }
            if (! fflush($manifest)) {
                throw new RuntimeException('Unable to flush the export manifest.');
            }
        } finally {
            fclose($manifest);
        }

        if (! File::move($directory.'/assets.partial.csv', $directory.'/assets.csv')) {
            throw new RuntimeException('Unable to finalize the export manifest.');
        }
        $this->info($directory);

        return self::SUCCESS;
    }
}

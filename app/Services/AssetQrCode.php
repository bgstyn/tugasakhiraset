<?php

namespace App\Services;

use App\Models\Asset;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\Result\ResultInterface;
use Endroid\QrCode\Writer\SvgWriter;

class AssetQrCode
{
    public function render(Asset $asset, string $format): ResultInterface
    {
        $writer = match ($format) {
            'png' => new PngWriter(),
            'svg' => new SvgWriter(),
        };

        return $writer->write(QrCode::create($asset->publicQrUrl())->setSize(440)->setMargin(30));
    }
}

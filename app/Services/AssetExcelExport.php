<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\StringCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\CellAlignment;
use OpenSpout\Common\Entity\Style\CellVerticalAlignment;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;

class AssetExcelExport
{
    private const HEADERS = [
        'No.', 'ID Aset TI', 'Nomor Inventaris', 'Nomor Seri', 'Nama Aset',
        'Kategori', 'Merek', 'Model', 'Spesifikasi', 'Gedung', 'Lantai', 'Ruangan',
        'Penanggung Jawab', 'Tahun', 'Status Aset', 'UID RFID', 'Status RFID',
        'Tanggal Input', 'Terakhir Diperbarui',
    ];

    private const WIDTHS = [6, 30, 26, 24, 34, 22, 20, 22, 48, 24, 12, 30, 28, 10, 28, 24, 20, 24, 24];

    public function write(Builder $query, string $path): void
    {
        $body = (new Style())->setFontName('Arial')->setFontSize(10)->setFontColor('172033')
            ->setCellAlignment(CellAlignment::LEFT)->setCellVerticalAlignment(CellVerticalAlignment::CENTER)->setShouldWrapText();
        $options = new Options();
        // OpenSpout marks registered styles immutable; keep the template separate.
        $options->DEFAULT_ROW_STYLE = clone $body;
        $options->SHOULD_CREATE_NEW_SHEETS_AUTOMATICALLY = false;
        foreach (self::WIDTHS as $index => $width) {
            $options->setColumnWidth($width, $index + 1);
        }
        $writer = new Writer($options);
        $writer->openToFile($path);
        try {
            $sheet = $writer->getCurrentSheet();
            $sheet->setName('Daftar Aset');
            $sheet->setSheetView((new SheetView())->setShowGridLines(false)->setFreezeRow(6)->setFreezeColumn('C'));
            $sheet->setPrintTitleRows('1:5');
            $writer->addRow(Row::fromValues(['']));
            $writer->addRow(Row::fromValues(['Inventaris TI'], (new Style())->setFontSize(14)->setFontBold()->setShouldWrapText(false))->setHeight(24));
            $writer->addRow(Row::fromValues(['Diekspor '.now()->format('d/m/Y H:i').' ('.config('app.timezone').')'], (new Style())->setFontColor('64748B')->setFontSize(10)->setShouldWrapText(false))->setHeight(20));
            $writer->addRow(Row::fromValues(['']));
            $header = (new Style())->setFontBold()->setFontColor('FFFFFF')->setBackgroundColor('1D4F91')
                ->setCellAlignment(CellAlignment::CENTER)->setCellVerticalAlignment(CellVerticalAlignment::CENTER)
                ->setShouldWrapText()->setBorder(new Border(new BorderPart(Border::RIGHT, 'FFFFFF', Border::WIDTH_THIN)));
            $writer->addRow(Row::fromValues(self::HEADERS, $header)->setHeight(32));
            $statusLabels = [
                'standby' => 'Standby', 'digunakan' => 'Digunakan', 'maintenance' => 'Dalam perbaikan',
                'rusak' => 'Rusak', 'fraud' => 'Hilang / investigasi', 'write_off' => 'Write off',
                'pending_fraud_approval' => 'Menunggu persetujuan investigasi',
                'pending_write_off_approval' => 'Menunggu persetujuan write off',
            ];
            $rfidLabels = ['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'belum_terdaftar' => 'Belum terdaftar'];
            $number = 0;
            // lazy() fetches bounded batches and preserves the list's chosen order.
            foreach ($query->lazy(500) as $asset) {
                ++$number;
                if ($number > 1048571) {
                    throw new \RuntimeException('Jumlah aset melampaui kapasitas satu lembar Excel.');
                }
                $values = [
                    $number, $asset->asset_id, $asset->government_inventory_number, $asset->serial_number,
                    $asset->name, $asset->category, $asset->brand, $asset->model, $asset->specification,
                    $asset->building, $asset->floor, $asset->room, $asset->current_user,
                    $asset->year === null ? null : (int) $asset->year,
                    $statusLabels[$asset->status] ?? $asset->status, $asset->rfid_uid,
                    filled($asset->rfid_uid) ? ($rfidLabels[$asset->rfid_status] ?? $asset->rfid_status) : 'Belum terhubung',
                    $asset->created_at, $asset->updated_at,
                ];
                $style = (clone $body)->setBackgroundColor($number % 2 === 0 ? 'EFF4FA' : 'FFFFFF')
                    ->setBorder(new Border(new BorderPart(Border::BOTTOM, 'DCE3EC', Border::WIDTH_THIN)));
                $cells = [];
                foreach ($values as $index => $value) {
                    $cellStyle = clone $style;
                    if (in_array($index, [0, 13], true)) {
                        $cellStyle->setCellAlignment(CellAlignment::CENTER)->setFormat('0');
                    } elseif ($index >= 17) {
                        $cellStyle->setFormat('dd/mm/yyyy hh:mm');
                    }
                    // Explicit strings retain leading zeros and prevent formula execution.
                    $cells[] = is_string($value) ? new StringCell($value, $cellStyle) : Cell::fromValue($value, $cellStyle);
                }
                $lines = 1;
                foreach ($values as $index => $value) {
                    if (is_string($value)) {
                        $cellLines = 0;
                        foreach (explode("\n", $value) as $line) {
                            $cellLines += max(1, (int) ceil(mb_strwidth($line) / (self::WIDTHS[$index] * .8)));
                        }
                        $lines = max($lines, $cellLines);
                    }
                }
                $writer->addRow((new Row($cells, $style))->setHeight(min(409, max(28, $lines * 15 + 10))));
            }
            $sheet->setAutoFilter(new AutoFilter(0, 5, count(self::HEADERS) - 1, 5 + $number));
        } finally {
            $writer->close();
        }
    }
}

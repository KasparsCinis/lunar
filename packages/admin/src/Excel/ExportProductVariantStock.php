<?php

namespace Lunar\Admin\Excel;

use Lunar\Models\Currency;
use Lunar\Models\ProductVariant;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductVariantStock
{
    public static function download(): BinaryFileResponse
    {
        $currency = Currency::getDefault();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Product ID');
        $sheet->setCellValue('B1', 'Product name');
        $sheet->setCellValue('C1', 'Variant ID');
        $sheet->setCellValue('D1', 'SKU');
        $sheet->setCellValue('E1', 'Variation');
        $sheet->setCellValue('F1', 'Stock');
        $sheet->setCellValue('G1', 'Price (ex tax)');
        $sheet->setCellValue('H1', 'Currency');

        $row = 2;

        ProductVariant::query()
            ->whereHas('product')
            ->with([
                'product',
                'values',
                'prices' => function ($q) use ($currency) {
                    $q->where('currency_id', $currency->id)
                        ->where('min_quantity', 1)
                        ->whereNull('customer_group_id');
                },
                'prices.currency',
            ])
            ->orderBy('product_id')
            ->orderBy('id')
            ->chunk(200, function ($variants) use ($sheet, &$row, $currency) {
                foreach ($variants as $variant) {
                    $price = $variant->prices->first();
                    $priceMajor = '';
                    if ($price) {
                        $factor = (int) $price->currency->factor;
                        $priceMajor = $factor > 0
                            ? $price->priceExTax()->value / $factor
                            : '';
                    }

                    $sheet->setCellValue('A'.$row, $variant->product->id);
                    $sheet->setCellValue('B'.$row, $variant->product->translateAttribute('name'));
                    $sheet->setCellValue('C'.$row, $variant->id);
                    $sheet->setCellValue('D'.$row, $variant->sku ?? '');
                    $sheet->setCellValue('E'.$row, $variant->getOption());
                    $sheet->setCellValue('F'.$row, $variant->stock);
                    $sheet->setCellValue('G'.$row, $priceMajor);
                    $sheet->setCellValue('H'.$row, $currency->code);

                    $row++;
                }
            });

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'product_stock_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'stock_export');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}

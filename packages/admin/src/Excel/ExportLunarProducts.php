<?php

namespace Lunar\Admin\Excel;

use Lunar\Models\Collection;
use Lunar\Models\Filters\FilterProduct;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Product;

class ExportLunarProducts
{
    public static function exportForCollection(Collection $collection)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'ID');
        $sheet->setCellValue('B1', 'Product Name LV');
        $sheet->setCellValue('C1', 'Product Name EN');
        $sheet->setCellValue('D1', 'Description LV');
        $sheet->setCellValue('E1', 'Description EN');
        $sheet->setCellValue('F1', 'SKU');
        $sheet->setCellValue('G1', 'Price');

        $column = "H";

        /** Filter headers */
        foreach ($collection->filtersAndParentFilters() as $filter) {
            $sheet->setCellValue($column . '1', $filter->translateAttribute('name'));
            $column++;
        }

        $row = 2;

        $collection->products()
            ->chunk(50, function ($products) use ($sheet, &$row, $collection) {
                /** @var Product $product */
                foreach ($products as $product) {
                    $sheet->setCellValue('A' . $row, $product->id);
                    $sheet->setCellValue('B' . $row, $product->translateAttribute('name', 'lv'));
                    $sheet->setCellValue('C' . $row, $product->translateAttribute('name', 'en'));
                    $sheet->setCellValue('D' . $row, $product->translateAttribute('description', 'lv'));
                    $sheet->setCellValue('E' . $row, $product->translateAttribute('description', 'en'));
                    $sheet->setCellValue('F' . $row, $product->variant?->sku);
                    $sheet->setCellValue('G' . $row, $product->prices()->first()?->priceExTax()->value / 100);

                    /** Export all filters */
                    $column = "H";

                    /** Filter headers */
                    foreach ($collection->filtersAndParentFilters() as $filter) {
                        $sheet->setCellValue($column . $row, FilterProduct::where([
                            'filter_id' => $filter->id,
                            'product_id' => $product->id
                        ])
                            ->first()
                            ?->value
                        );

                        $column++;
                    }

                    $row++;
                }
            });
        
        // (Optional) Auto-size columns
        foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create Excel file in memory
        $writer = new Xlsx($spreadsheet);
        $fileName = 'export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);

        // Return file as download response
        return response()->download($temp_file, $fileName)->deleteFileAfterSend(true);
    }
}

<?php

namespace Lunar\Admin\Excel;

use Lunar\Models\Currency;
use Lunar\Models\CustomerGroup;
use Lunar\Models\ProductVariant;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportProductVariantPrices
{
    public const DEFAULT_GROUP_HANDLE = 'PAR';

    public static function download(array $groupHandles): BinaryFileResponse
    {
        $currency = Currency::getDefault();
        $factor = (int) ($currency?->factor ?? 0);

        $handles = array_values(array_unique(array_filter($groupHandles, fn ($handle) => filled($handle))));
        $includeDefault = in_array(self::DEFAULT_GROUP_HANDLE, $handles, true);
        $customerGroupHandles = array_values(array_filter(
            $handles,
            fn ($handle) => $handle !== self::DEFAULT_GROUP_HANDLE
        ));

        $customerGroups = CustomerGroup::query()
            ->whereIn('handle', $customerGroupHandles)
            ->get()
            ->keyBy('handle');

        $orderedHandles = [];
        if ($includeDefault) {
            $orderedHandles[] = self::DEFAULT_GROUP_HANDLE;
        }
        foreach ($customerGroupHandles as $handle) {
            if ($customerGroups->has($handle)) {
                $orderedHandles[] = $handle;
            }
        }

        $groupIds = $customerGroups->pluck('id');

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'SKU');
        $sheet->setCellValue('B1', 'Customer group handle');
        $sheet->setCellValue('C1', 'Price');

        $row = 2;

        ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->withoutTrashed())
            ->with([
                'prices' => function ($q) use ($currency, $groupIds, $includeDefault) {
                    $q->where('currency_id', $currency?->id)
                        ->where('min_quantity', 1)
                        ->where(function ($q) use ($groupIds, $includeDefault) {
                            if ($includeDefault && $groupIds->isNotEmpty()) {
                                $q->whereNull('customer_group_id')
                                    ->orWhereIn('customer_group_id', $groupIds);
                            } elseif ($includeDefault) {
                                $q->whereNull('customer_group_id');
                            } else {
                                $q->whereIn('customer_group_id', $groupIds);
                            }
                        })
                        ->with('currency');
                },
            ])
            ->orderBy('product_id')
            ->orderBy('id')
            ->chunk(200, function ($variants) use ($sheet, &$row, $orderedHandles, $customerGroups, $factor) {
                foreach ($variants as $variant) {
                    foreach ($orderedHandles as $handle) {
                        $priceValue = '';

                        if ($handle === self::DEFAULT_GROUP_HANDLE) {
                            $price = $variant->prices->first(fn ($price) => $price->customer_group_id === null);
                        } else {
                            $groupId = $customerGroups->get($handle)?->id;
                            $price = $groupId
                                ? $variant->prices->firstWhere('customer_group_id', $groupId)
                                : null;
                        }

                        if ($price && $factor > 0) {
                            $priceValue = $price->price->value / $factor;
                        }

                        $sheet->setCellValue('A'.$row, $variant->sku ?? '');
                        $sheet->setCellValue('B'.$row, $handle);
                        $sheet->setCellValue('C'.$row, $priceValue);

                        $row++;
                    }
                }
            });

        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'product_prices_export_'.now()->format('Y-m-d_H-i-s').'.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), 'prices_export');
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}

<?php

namespace Lunar\Jobs\Imports;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lunar\Facades\DB;
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Brand;
use Lunar\Models\Collection;
use Lunar\Models\Currency;
use Lunar\Models\Excel\Import;
use Lunar\Models\Filters\Filter;
use Lunar\Models\Filters\FilterProduct;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use Lunar\Models\ProductVariant;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;

class ImportStockJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 0;
    public $failOnTimeout = true;

    protected string $importId;
    protected Import $import;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function handle(): void
    {
        DB::disableQueryLog();

        try {
            $this->import = Import::findOrFail($this->importId);

            $disk = Storage::disk(config('media-library.disk_name'));
            $currency = Currency::where('code', 'eur')->first();
            $excelPath = "";

            $excelMedia = $this->import->getFirstMedia('import_excel');
            if (!$excelMedia) {
                $this->import->status = Import::STATUS_ERROR;
                $this->import->progress = 'No Excel file attached to import.';
                $this->import->saveOrFail();
                return;
            } else {
                /** Download excel */
                $excelPath = tempnam(sys_get_temp_dir(), 'excel_');
                $stream = $disk->readStream($excelMedia->getPathRelativeToRoot());

                file_put_contents($excelPath, stream_get_contents($stream));
                fclose($stream);
            }

            $this->import->status = Import::STATUS_IN_PROGRESS;
            $this->import->progress = 'Updating stocks';
            $this->import->saveOrFail();

            $spreadsheet = IOFactory::load($excelPath);
            $sheet = $spreadsheet->getActiveSheet();
            $mapping = $this->import->column_mapping;

            /** @var $rowModel Row */
            foreach ($sheet->getRowIterator() as $rowIndex => $rowModel) {
                $cellIterator = $rowModel->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                $row = [];
                foreach ($cellIterator as $cell) {
                    $row[] = $cell->getValue();
                }

                if ($rowIndex == 1) {
                    continue;
                }
                if (!array_filter($row)) {
                    continue;
                }

                if ($rowIndex % 20 == 0) {
                    $this->import->progress = "Updated {$rowIndex} rows";
                    $this->import->saveOrFail();
                }

                $data = [];

                foreach ($mapping as $key => $columnType) {
                    if (is_null($columnType)) {
                        continue;
                    }
                    if (is_null($row[$columnType]) || $row[$columnType] == '') {
                        continue;
                    }

                    $data[$key] = $row[$columnType];
                }

                if (isset($data['sku']) && $product = ProductVariant::where('sku', '=', $data['sku'])->first()) {
                    $product->stock = $data['stock'];
                    $product->saveOrFail();
                }

                if ($rowIndex % 25 === 0) {
                    gc_collect_cycles();
                    DB::disconnect();
                }
            }

            if ($excelPath) {
                @unlink($excelPath);
            }

            $this->import->status = Import::STATUS_SUCCESS;
            $this->import->progress = 'Stocks updated';
            $this->import->saveOrFail();
        } catch (\Exception | \Throwable $e) {
            Log::error('Failed to import', [
                'error' => $e->getMessage(),
            ]);

            if ($this->import) {
                $this->import->status = Import::STATUS_ERROR;
                $this->import->progress = Str::limit('Failed - ' . $e->getMessage(), 200);
                $this->import->saveOrFail();
            }
        }
    }
}

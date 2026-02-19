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
use Lunar\Models\Filters\FilterProduct;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Row;
use ZipArchive;

class ImportExcelJob implements ShouldQueue
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
            $zipPath = "";

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
            $this->import->progress = 'Importing';
            $this->import->saveOrFail();

            $zipImagesPath = null;
            if ($zipMedia = $this->import->getFirstMedia('import_zip')) {
                /** Download zip */
                $zipPath = tempnam(sys_get_temp_dir(), 'zip_');
                $stream = $disk->readStream($zipMedia->getPathRelativeToRoot());

                file_put_contents($zipPath, stream_get_contents($stream));
                fclose($stream);

                $zipImagesPath = storage_path('app/tmp/import_images_' . $this->import->id);
                @mkdir($zipImagesPath, 0777, true);

                $zip = new ZipArchive();
                if ($zip->open($zipPath) === true) {
                    $zip->extractTo($zipImagesPath);
                    $zip->close();
                }
            }

            $spreadsheet = IOFactory::load($excelPath);
            $sheet = $spreadsheet->getActiveSheet();
            $mapping = $this->import->column_mapping;

            $collection = Collection::findOrFail($this->import->collection_id);
            $prefix = config('lunar.database.table_prefix');
            $nextPosition = ($collection->products()->max( "{$prefix}collection_product.position") ?? 1) + 1;

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
                    $this->import->progress = "Imported {$rowIndex}";
                    $this->import->saveOrFail();
                }

                $data = [
                    'images' => []
                ];

                foreach ($mapping as $key => $columnType) {
                    if (is_null($columnType)) {
                        continue;
                    }
                    if (is_null($row[$key]) || $row[$key] == '') {
                        continue;
                    }

                    if ($columnType == 'image') {
                        $data['images'][] = $row[$key];
                    } else {
                        $data[$columnType] = $row[$key];
                    }
                }

                if (isset($data['id']) && $product = Product::find($data['id'])) {
                    /** @var $product Product */

                    if (isset($data['name_en'])
                        || isset($data['name_lv'])
                        || isset($data['description_en'])
                        || isset($data['description_lv'])
                    ) {
                        $product->attribute_data = collect([
                            'name' => new TranslatedText([
                                'en' => $data['name_en'] ?? '',
                                'lv' => $data['name_lv'] ?? '',
                            ]),
                            'description' => new TranslatedText([
                                'en' => $data['description_en'] ?? '',
                                'lv' => $data['description_lv'] ?? '',
                            ]),
                        ]);
                        $product->saveOrFail();
                    }

                    if (isset($data['sku'])) {
                        $product->variant->sku = $data['sku'];
                        $product->variant->saveOrFail();
                    }
                    if (isset($data['price'])) {
                        $price = $product->variant->prices()->first();

                        $price->price = $this->parsePrice($data['price'] ?? 0);
                        $price->saveOrFail();
                    }
                    if (isset($data['stock'])) {
                        $product->variant->stock = $data['stock'];
                        $product->variant->saveOrFail();
                    }
                } else {
                    $product = Product::create([
                        'status' => 'published',
                        'product_type_id' => ProductType::first()->id,
                        'attribute_data' => collect([
                            'name' => new TranslatedText([
                                'en' => $data['name_en'] ?? '',
                                'lv' => $data['name_lv'] ?? '',
                            ]),
                            'description' => new TranslatedText([
                                'en' => $data['description_en'] ?? '',
                                'lv' => $data['description_lv'] ?? '',
                            ]),
                        ]),
                    ]);

                    $product->collections()->attach($this->import->collection_id, [
                        'position' => $nextPosition
                    ]);

                    $variant = $product->variants()->create([
                        'sku' => $data['sku'] ?? 'SKU-' . uniqid(),
                        'stock' => $data['stock'] ?? 0,
                        'tax_class_id' => 1, //@todo
                    ]);

                    $variant->prices()->create([
                        'currency_id' => $currency->id,
                        'price' => $this->parsePrice($data['price'] ?? 0),
                        'min_quantity' => 1
                    ]);

                    $nextPosition++;
                }

                if (isset($data['brand']) && $data['brand']) {
                    $brand = Brand::firstOrCreate([
                        'name' => $data['brand'],
                    ]);

                    $product->brand_id = $brand->id;
                    $product->saveOrFail();
                }

                if (isset($data['min_stock']) && $data['min_stock']) {
                    $product->variant->min_quantity = $data['min_stock'];
                    $product->variant->saveOrFail();
                }

                foreach ($mapping as $key => $columnType) {
                    if (Str::contains($columnType, 'filter-')
                        && $data[$columnType]
                        && $id = explode('-', $columnType)[1] ?? null
                    ) {
                        FilterProduct::updateOrCreate(
                            [
                                'filter_id' => $id,
                                'product_id' => $product->id,
                            ],
                            [
                                'value' => $data[$columnType],
                            ]
                        );
                    }
                }

                // Attach images if zip provided
                if (!empty($data['images'])) {
                    foreach ($data['images'] as $imagePath) {
                        $imagePathFromZip = $this->findImagePath($zipImagesPath, trim($imagePath));

                        if ($imagePathFromZip && file_exists($imagePathFromZip)) {
                            $product->addMedia($imagePathFromZip)
                                ->preservingOriginal()
                                ->toMediaCollection('images');
                        } else {
                            /**
                             * Sometimes zip files are too big - in which case images might already be uploaded beforehand to storage
                             * Check if the images don't already exist
                             */
                            if ($disk->exists('zipImages/' . $imagePath)) {
                                $tempFile = tempnam(sys_get_temp_dir(), 'img_');
                                $stream = $disk->readStream('zipImages/' . $imagePath);

                                if ($stream) {
                                    file_put_contents($tempFile, stream_get_contents($stream));
                                    fclose($stream);

                                    $product->addMedia($tempFile)
                                        ->usingFileName(basename($imagePath))
                                        ->preservingOriginal()
                                        ->toMediaCollection('images');

                                    // Clean up temporary file
                                    @unlink($tempFile);
                                }
                            }
                        }
                    }
                }

                Log::info("Imported product: {$product->translateAttribute('name', 'en')}");

                if ($rowIndex % 25 === 0) {
                    gc_collect_cycles();
                    DB::disconnect();
                }
            }

            if ($zipImagesPath) {
                Storage::deleteDirectory($zipImagesPath);
            }
            if ($excelPath) {
                @unlink($excelPath);
            }
            if ($zipPath) {
                @unlink($zipPath);
            }

            $this->import->status = Import::STATUS_SUCCESS;
            $this->import->progress = 'Imported';
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

    protected function parsePrice($price)
    {
        return (int) (floatval(str_replace(',', '.', $price)) * 100);
    }

    protected function findImagePath($basePath, $imageName)
    {
        $files = glob($basePath . '/*');

        foreach ($files as $file) {
            if (strcasecmp(basename($file), $imageName) === 0) {
                return $file;
            }
        }

        return null;
    }
}

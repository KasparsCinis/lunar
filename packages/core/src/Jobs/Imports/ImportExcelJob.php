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
use Lunar\FieldTypes\TranslatedText;
use Lunar\Models\Currency;
use Lunar\Models\Excel\Import;
use Lunar\Models\Product;
use Lunar\Models\ProductType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ZipArchive;

class ImportExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 1;

    protected string $importId;
    protected Import $import;

    public function __construct(string $importId)
    {
        $this->importId = $importId;
    }

    public function handle(): void
    {
        try {
            $this->import = Import::findOrFail($this->importId);
            $currency = Currency::where('code', 'eur')->first();

            $excelMedia = $this->import->getFirstMedia('import_excel');
            if (!$excelMedia) {
                $this->import->status = Import::STATUS_ERROR;
                $this->import->progress = 'No Excel file attached to import.';
                $this->import->saveOrFail();
                return;
            }

            $this->import->status = Import::STATUS_IN_PROGRESS;
            $this->import->progress = 'Importing';
            $this->import->saveOrFail();

            $excelPath = $excelMedia->getPath();

            $zipImagesPath = null;
            if ($zipMedia = $this->import->getFirstMedia('import_zip')) {
                $zipPath = $zipMedia->getPath();
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
            $rows = $sheet->toArray(null, true, true, true);

            $mapping = $this->import->column_mapping;

            foreach ($rows as $rowIndex => $row) {
                $row = array_values($row);

                if ($rowIndex == 1) {
                    continue;
                }
                if (!array_filter($row)) {
                    continue;
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

                // Create the product
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

                $product->collections()->attach($this->import->collection_id);

                // Create variant
                $variant = $product->variants()->create([
                    'sku' => $data['sku'] ?? 'SKU-' . uniqid(), //@todo
                    'tax_class_id' => 1, //@todo
                ]);

                $variant->prices()->create([
                    'currency_id' => $currency->id,
                    'price' => $this->parsePrice($data['price'] ?? 0),
                    'min_quantity' => 1
                ]);

                // Attach images if zip provided
                if ($zipImagesPath && !empty($data['images'])) {
                    foreach ($data['images'] as $imagePath) {
                        $imagePath = $this->findImagePath($zipImagesPath,  trim($imagePath));

                        if ($imagePath && file_exists($imagePath)) {
                            $product->addMedia($imagePath)
                                ->preservingOriginal()
                                ->toMediaCollection('images');
                        } else {
                            /**
                             * Sometimes zip files are too big - in which case images might already be uploaded beforehand to storage
                             * Check if the images don't already exist
                             */
                            if (Storage::exists('zipImages/' . $imagePath)) {
                                $tempFile = tempnam(sys_get_temp_dir(), 'img_');
                                $stream = Storage::readStream('zipImages/' . $imagePath);

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
            }

            if ($zipImagesPath) {
                Storage::deleteDirectory($zipImagesPath);
            }

            $this->import->status = Import::STATUS_SUCCESS;
            $this->import->progress = 'Imported';
            $this->import->saveOrFail();
        } catch (\Exception $e) {
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

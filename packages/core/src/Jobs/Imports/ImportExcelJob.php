<?php

namespace Lunar\Jobs\Imports;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Lunar\Models\Excel\Import;
use Maatwebsite\Excel\Facades\Excel;

class ImportExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

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

            $excelMedia = $this->import->getFirstMedia('import_excel');
            if (!$excelMedia) {
                Log::error('No Excel file attached to import.');
                return;
            }

            $excelPath = $excelMedia->getPath();

            $zipImagesPath = null;
            if ($zipMedia = $this->import->getFirstMedia('import_zip')) {
                $zipPath = $zipMedia->getPath();
                $zipImagesPath = storage_path('app/tmp/import_images_' . $this->import->id);
                @mkdir($zipImagesPath, 0777, true);

                $zip = new ZipArchive;
                if ($zip->open($zipPath) === true) {
                    $zip->extractTo($zipImagesPath);
                    $zip->close();
                }
            }

            $rows = Excel::toCollection(null, $excelPath)->first();
            $mapping = $this->import->column_mapping;

            foreach ($rows as $rowIndex => $row) {
                // Skip empty rows
                if (!array_filter($row->toArray())) {
                    continue;
                }

                // Map row columns according to import mapping
                $data = [];
                foreach ($mapping as $key => $column) {
                    $data[$key] = $row[$column] ?? null;
                }

                // Create the product
                $product = Product::create([
                    'status' => 'published',
                    'product_type_id' => ProductType::first()->id, // you may adjust this
                    'attribute_data' => [
                        'name' => [
                            'en' => $data['name_en'] ?? '',
                            'lv' => $data['name_lv'] ?? '',
                        ],
                        'description' => [
                            'en' => $data['description_en'] ?? '',
                            'lv' => $data['description_lv'] ?? '',
                        ],
                    ],
                ]);

                // Create variant
                $variant = $product->variants()->create([
                    'sku' => $data['sku'] ?? 'SKU-' . uniqid(),
                    'price' => $this->parsePrice($data['price'] ?? 0),
                    //'stock' => 10, // or set dynamically //@todo
                    //'tax_class_id' => 1, // adjust to your setup //@todo
                ]);

                // Attach images if zip provided
                if ($zipImagesPath && !empty($data['image'])) {
                    $imageName = trim($data['image']);
                    $imagePath = $this->findImagePath($zipImagesPath, $imageName);

                    if ($imagePath && file_exists($imagePath)) {
                        $product->addMedia($imagePath)
                            ->preservingOriginal()
                            ->toMediaCollection('images');
                    }
                }

                Log::info("Imported product: {$product->translateAttribute('name', 'en')}");
            }

            if ($zipImagesPath) {
                Storage::deleteDirectory($zipImagesPath);
            }

            $this->import->progress = 'Imported';
            $this->import->saveOrFail();
        } catch (\Exception $e) {
            if ($this->import) {
                $this->import->progress = 'Failed - ' . $e->getMessage();
                $this->import->save();
            }

            Log::error('Failed to import', [
                'error' => $e->getMessage(),
            ]);
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

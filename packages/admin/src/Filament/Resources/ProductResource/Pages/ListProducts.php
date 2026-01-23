<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseListRecords;
use Lunar\Facades\DB;
use Lunar\Helpers\CurrencyHelper;
use Lunar\Jobs\Imports\ImportExcelJob;
use Lunar\Models\Attribute;
use Lunar\Models\Currency;
use Lunar\Models\Excel\Import;
use Lunar\Models\Product;
use Lunar\Models\TaxClass;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Filament\Forms\Form;

class ListProducts extends BaseListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\Action::make('updateStock')
                ->label('Update stock')
                ->modalHeading('Update product stocks')
                ->form([
                    Wizard::make([
                        Step::make('Upload file')
                            ->schema([
                                SpatieMediaLibraryFileUpload::make('excel_file')
                                    ->collection('import_excel')
                                    ->label('Excel File')
                                    ->required()
                                    ->acceptedFileTypes([
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                    ])
                                    ->maxSize(10240)
                                    ->preserveFilenames()
                                    ->downloadable()
                                    ->openable()
                                    ->hint('Upload a single Excel file (.xls or .xlsx)')
                                    ->disk(config('media-library.disk_name'))
                                    ->reactive()
                                    ->afterStateUpdated(function (TemporaryUploadedFile $state, callable $set) {
                                        $tempPath = $state->getRealPath();

                                        try {
                                            $spreadsheet = IOFactory::load($tempPath);
                                            $sheet = $spreadsheet->getSheet(0);
                                            $headerRow = $sheet->rangeToArray(
                                                'A1:' . $sheet->getHighestColumn() . '1',
                                                null,
                                                true,
                                                true,
                                                true
                                            )[1];

                                            $columns = array_values($headerRow);
                                            $columns = array_filter($columns);

                                            $set('excel_headers', $columns);
                                        } catch (\Throwable $e) {
                                            logger()->error('Failed to parse Excel: ' . $e->getMessage());
                                        }
                                    }),
                            ]),
                        Step::make('Map fields')
                            ->schema([
                                Select::make('mapping.sku')
                                    ->label('SKU')
                                    ->options(fn (callable $get) =>
                                        array_combine(
                                            $get('excel_headers') ?? [],
                                            $get('excel_headers') ?? []
                                        )
                                    )
                                    ->required(),

                                Select::make('mapping.stock')
                                    ->label('Stock')
                                    ->options(fn (callable $get) =>
                                        array_combine(
                                            $get('excel_headers') ?? [],
                                            $get('excel_headers') ?? []
                                        )
                                    )
                                    ->required(),
                            ]),
                    ])
                        ->skippable(false),
                ])
                ->action(function (array $data, Form $form) {
                    $record = Import::create([
                        'status' => Import::STATUS_PENDING,
                        'column_mapping' => $data['mapping'],
                        'progress' => 'Preparing to import',
                        'type' => Import::TYPE_INVENTORY
                    ]);

                    $form->model($record)->saveRelationships();

                    /*ImportExcelJob::dispatch($record->id)
                        ->delay(now()->addSeconds(5));*/

                    //$this->success('Excel import started');
                }),
            Actions\CreateAction::make()->createAnother(false)->form(
                    static::createActionFormInputs()
                )->using(
                    fn (array $data, string $model) => static::createRecord($data, $model)
                )->successRedirectUrl(fn (Model $record): string => ProductResource::getUrl('edit', [
                    'record' => $record,
                ])),
        ];
    }

    public static function createActionFormInputs(): array
    {
        return [
            Grid::make(2)->schema([
                ProductResource::getBaseNameFormComponent(),
                ProductResource::getProductTypeFormComponent()->required(),
            ]),
            Grid::make(2)->schema([
                ProductResource::getSkuFormComponent(),
                ProductResource::getBasePriceFormComponent(),
            ]),
        ];
    }

    public static function createRecord(array $data, string $model): Model
    {
        $currency = Currency::getDefault();

        $nameAttribute = Attribute::whereAttributeType(
            $model::morphName()
        )
            ->whereHandle('name')
            ->first()
            ->type;

        DB::beginTransaction();
        $product = $model::create([
            'status' => 'draft',
            'product_type_id' => $data['product_type_id'],
            'attribute_data' => [
                'name' => new $nameAttribute($data['name']),
            ],
        ]);
        $variant = $product->variants()->create([
            'tax_class_id' => TaxClass::getDefault()->id,
            'sku' => $data['sku'],
        ]);
        $variant->prices()->create([
            'min_quantity' => 1,
            'currency_id' => $currency->id,
            'price' => (int) bcmul(CurrencyHelper::cleanup($data['base_price']), $currency->factor),
        ]);
        DB::commit();

        return $product;
    }

    public function getDefaultTabs(): array
    {
        return [
            'all' => Tab::make(__('lunarpanel::product.tabs.all')),
            'published' => Tab::make('Published')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'published')),
            'draft' => Tab::make('Draft')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(Product::query()->where('status', 'draft')->count()),
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}

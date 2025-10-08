<?php

namespace Lunar\Admin\Filament\Resources\CollectionResource\Pages;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lunar\Admin\Filament\Resources\CollectionResource;
use Lunar\Admin\Support\Forms\Components\Attributes;
use Lunar\Admin\Support\Pages\BaseManageRelatedRecords;
use Lunar\Facades\ModelManifest;
use Lunar\Jobs\Imports\ImportExcelJob;
use Lunar\Models\Excel\Import;
use Lunar\Models\Filters\Filter;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\Select;
use Mary\Traits\Toast;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;

class ManageCollectionImports extends BaseManageRelatedRecords
{
    use Toast;

    protected static string $resource = CollectionResource::class;

    protected static string $relationship = 'imports';

    public function getTitle(): string|Htmlable
    {
        return 'Import/update from excel';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-tray';
    }

    public static function getNavigationLabel(): string
    {
        return 'Excels';
    }

    public function getBreadcrumbs(): array
    {
        $record = $this->getRecord();

        $crumbs = static::getResource()::getCollectionBreadcrumbs($record);

        $crumbs[] = $this->getBreadcrumb();

        return $crumbs;
    }

    public function getBreadcrumb(): string
    {
        return 'Excels';
    }

    private function mapColumns()
    {
        $filters = [];

        foreach ($this->record->filtersAndParentFilters() as $filter) {
            $filters['filter-' . $filter->id] = $filter->translateAttribute('name');
        }

        return [
            'name_lv' => 'Product Name LV',
            'name_en' => 'Product Name EN',
            'description_lv' => 'Description LV',
            'description_en' => 'Description EN',
            'sku' => 'SKU',
            'price' => 'Price',
            'image' => 'Image'
        ] + $filters;
    }

    private function steps()
    {
        return [
            Step::make('Upload Files')
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
                        ->reactive(),

                    SpatieMediaLibraryFileUpload::make('zip_file')
                        ->collection('import_zip')
                        ->label('ZIP images (optional)')
                        ->acceptedFileTypes(['application/zip'])
                        ->maxSize(51200)
                        ->preserveFilenames()
                        ->downloadable()
                        ->openable()
                        ->hint('Optional ZIP file containing images'),
                ]),
            Step::make('Column mapping')
                ->schema([
                    Repeater::make('column_mapping')
                        ->label('Column Mappings')
                        ->schema([
                            Forms\Components\TextInput::make('column_name')
                                ->label('Excel Column')
                                ->disabled(),

                            Select::make('mapped_to')
                                ->label('Map To')
                                ->options($this->mapColumns())
                                ->searchable(),
                        ])
                        ->visible(fn($get) => filled($get('excel_columns')))
                        ->defaultItems(0)
                        ->columns(2)
                        ->reactive()
                        ->addable(false),
                ])
                ->afterStateUpdated(function ($component, $state, $set, $get) {
                    $existingMapping = $get('column_mapping');

                    if ($existingMapping) {
                        return;
                    }

                    $temporaryUploadedFile = $get('excel_file');

                    if (!$temporaryUploadedFile) {
                        return;
                    }
                    if (is_array($temporaryUploadedFile)) {
                        $temporaryUploadedFile = array_values($temporaryUploadedFile)[0];
                    }

                    $tempPath = $temporaryUploadedFile->getRealPath();

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

                        // Now populate your repeater / form state
                        $set('excel_columns', $columns);
                        $set('column_mapping', collect($columns)->map(function ($col) {
                            return [
                                'column_name' => $col,
                                'mapped_to' => str_contains($col, 'image') ? 'image' : null,
                            ];
                        })->toArray());
                    } catch (\Throwable $e) {
                        logger()->error('Failed to parse Excel: ' . $e->getMessage());
                    }
                })
        ];
    }

    public function table(Table $table): Table
    {
        $record = $this->getOwnerRecord();

        return $table->columns([
            Tables\Columns\TextColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn (Model $record) => $record->type),
            Tables\Columns\TextColumn::make('attribute_data.name')
                ->label('Name')
                ->formatStateUsing(fn (Model $record) => $record->attr('name')),
        ])
            ->actions([])
            ->headerActions([
                Tables\Actions\Action::make('New excel import')
                    ->steps($this->steps())
                    ->extraAttributes(['data-submit-scope' => 'all'])
                    ->action(function (array $data, Form $form) {
                        //@todo - check

                        $record = Import::create([
                            'collection_id' => $this->record->id,
                            'status' => Import::STATUS_PENDING,
                            'column_mapping' => $data['column_mapping'],
                            'progress' => 'Pending...'
                        ]);

                        $form->model($record)->saveRelationships();

                        ImportExcelJob::dispatch($record->id)
                            ->delay(now()->addSeconds(5));

                        $this->success('Excel import started');
                    })
            ]);
    }
}


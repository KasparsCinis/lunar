<?php

namespace Lunar\Admin\Filament\Resources\CollectionResource\Pages;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Lunar\Admin\Excel\ExportLunarProducts;
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
            'id' => 'ID',
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
                        ->disk(config('media-library.disk_name'))
                        ->reactive(),

                    SpatieMediaLibraryFileUpload::make('zip_file')
                        ->collection('import_zip')
                        ->label('ZIP images (optional)')
                        ->acceptedFileTypes([
                            'application/zip',
                            '.zip',
                            'application/x-zip-compressed'
                        ])
                        ->maxSize(51200)
                        ->preserveFilenames()
                        ->downloadable()
                        ->openable()
                        ->disk(config('media-library.disk_name'))
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
                        ->addable(false)
                        ->reorderable(false)
                        ->deletable(false),
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
                        $mappings = $this->mapColumns();
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
                        $set('column_mapping', collect($columns)->map(function ($col) use ($mappings) {
                            $preMap = null;

                            if (in_array($col, $mappings)) {
                                $preMap = array_search($col, $mappings, true);
                            } else {
                                $preMap = str_contains($col, 'image') ? 'image' : null;
                            }
                            
                            return [
                                'column_name' => $col,
                                'mapped_to' => $preMap,
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
            Tables\Columns\TextColumn::make('id')
                ->label('ID'),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->color(fn (int $state): string => match ($state) {
                    Import::STATUS_PENDING => 'gray',
                    Import::STATUS_IN_PROGRESS => 'info',
                    Import::STATUS_ERROR => 'danger',
                    Import::STATUS_SUCCESS => 'success'
                })
                ->formatStateUsing(fn (int $state): string => match ($state) {
                    Import::STATUS_PENDING => 'Pending',
                    Import::STATUS_IN_PROGRESS => 'In progress',
                    Import::STATUS_ERROR => 'Error',
                    Import::STATUS_SUCCESS => 'Success',
                })
                ->alignCenter(),
            Tables\Columns\TextColumn::make('progress')
                ->label('Progress')
                ->wrap(),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Created at'),
        ])
            ->actions([
                Action::make('Try again')
                    ->requiresConfirmation()
                    ->action(function (Import $record) {
                        $record->update([
                            'status' => Import::STATUS_PENDING,
                            'progress' => 'Preparing to import'
                        ]);

                        ImportExcelJob::dispatch($record->id)
                            ->delay(now()->addSeconds(5));

                        $this->success('Import re-importing');

                        return redirect(request()->header('Referer'));
                    })
                    ->visible(fn (Import $record) => $record->status == Import::STATUS_ERROR),
                Tables\Actions\DeleteAction::make('Delete')
            ])
            ->headerActions([
                Tables\Actions\Action::make('Export')
                    ->action(function (Action $action) {
                        return ExportLunarProducts::exportForCollection($this->record);
                    }),
                Tables\Actions\Action::make('Import')
                    ->steps($this->steps())
                    ->extraAttributes(['data-submit-scope' => 'all'])
                    ->action(function (array $data, Form $form, Action $action) {
                        $mappedColumns = array_map(fn($item) => $item['mapped_to'], $data['column_mapping']);
                        $missingColumns = [];

                        if (!in_array('sku', $mappedColumns)) {
                            $missingColumns[] = 'SKU';
                        }
                        if (!in_array('price', $mappedColumns)) {
                            $missingColumns[] = 'Price';
                        }

                        if ($missingColumns) {
                            $action->failureNotification(
                                fn () => Notification::make('refund_failure')
                                    ->color('danger')
                                    ->title("Missing columns - " . implode(',', $missingColumns))
                            );

                            $action->failure();
                            $action->halt();

                            return;
                        }

                        $record = Import::create([
                            'collection_id' => $this->record->id,
                            'status' => Import::STATUS_PENDING,
                            'column_mapping' => $mappedColumns,
                            'progress' => 'Preparing to import'
                        ]);

                        $form->model($record)->saveRelationships();

                        ImportExcelJob::dispatch($record->id)
                            ->delay(now()->addSeconds(5));

                        $this->success('Excel import started');
                    })
            ])
            ->defaultSort('created_at', 'desc');
    }
}


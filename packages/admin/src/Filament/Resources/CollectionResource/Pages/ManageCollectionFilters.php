<?php

namespace Lunar\Admin\Filament\Resources\CollectionResource\Pages;

use Filament\Forms;
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
use Lunar\Models\Filters\Filter;

class ManageCollectionFilters extends BaseManageRelatedRecords
{
    protected static string $resource = CollectionResource::class;

    protected static string $relationship = 'filters';

    public function getTitle(): string|Htmlable
    {
        return 'Filters';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-funnel';
    }

    public static function getNavigationLabel(): string
    {
        return 'Filters';
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
        return 'Filters';
    }

    public function getEloquentQuery(): Builder|Relation|null
    {
        $collection = $this->getOwnerRecord();

        $ids = [$collection->id];

        while ($collection->parent) {
            $collection = $collection->parent;
            $ids[] = $collection->id;
        }

        return Filter::whereIn('collection_id', $ids);
    }

    function paginateTableQuery(Builder $query): Paginator|CursorPaginator
    {
        return $this
            ->getEloquentQuery()
            ->simplePaginate(($this->getTableRecordsPerPage() === 'all')
                ? $query->count()
                : $this->getTableRecordsPerPage()
            );
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Attributes::make()->using(Filter::class),
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            1 => 'Dropdown',
                            2 => 'Dropdown (multiple)',
                            3 => 'Slider'
                        ])
                ])->columnSpan(2),
        ]);
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
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('Create filter')
        ]);
    }
}

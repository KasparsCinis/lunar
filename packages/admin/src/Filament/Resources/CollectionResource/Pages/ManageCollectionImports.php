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

class ManageCollectionImports extends BaseManageRelatedRecords
{
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

    public function form(Form $form): Form
    {
        //@todo
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
        //@todo
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
                Tables\Actions\CreateAction::make()->label('New excel import')
            ]);
    }
}


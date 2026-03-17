<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\BannerResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Collection;
use Lunar\Models\Contracts\Banner as BannerContract;

class BannerResource extends BaseResource
{
    protected static ?string $model = BannerContract::class;

    protected static ?int $navigationSort = 50;

    protected static int $globalSearchResultsLimit = 5;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    public static function getLabel(): string
    {
        return 'Banner';
    }

    public static function getPluralLabel(): string
    {
        return 'Banners';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.catalog');
    }

    public static function getDefaultForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema(
                        static::getMainFormComponents(),
                    ),
            ])
            ->columns(1);
    }

    /**
     * @return array<int, Component>
     */
    protected static function getMainFormComponents(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Name')
                ->required()
                ->maxLength(255),
            Forms\Components\Select::make('location')
                ->label('Location')
                ->options([
                    'homepage' => 'Homepage',
                    'category' => 'Category',
                    'special' => 'Special',
                ])
                ->required()
                ->live(),
            Forms\Components\TextInput::make('special_value')
                ->label('Special value')
                ->required(fn (callable $get) => $get('location') === 'special')
                ->visible(fn (callable $get) => $get('location') === 'special')
                ->maxLength(255),
            Forms\Components\Select::make('collection_id')
                ->label('Category')
                ->options(function () {
                    return Collection::query()
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (Collection $collection) => [
                            $collection->id => $collection->translateAttribute('name'),
                        ]);
                })
                ->searchable()
                ->preload()
                ->visible(fn (callable $get) => $get('location') === 'category'),
            SpatieMediaLibraryFileUpload::make('media')
                ->label('Media')
                ->collection('banners')
                ->disk(config('media-library.disk_name'))
                ->preserveFilenames()
                ->openable()
                ->downloadable()
                ->required()
                ->acceptedFileTypes([
                    'image/*',
                    'video/*',
                ]),
            Forms\Components\TextInput::make('cta_url')
                ->label('Button URL')
                ->maxLength(2048),
            Forms\Components\Toggle::make('is_active')
                ->label('Active')
                ->default(true),
            Forms\Components\TextInput::make('position')
                ->label('Position')
                ->numeric()
                ->default(0),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Starts at')
                ->seconds(false),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Ends at')
                ->seconds(false),
        ];
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->deferLoading();
    }

    protected static function getTableColumns(): array
    {
        return [
            SpatieMediaLibraryImageColumn::make('media')
                ->collection('banners')
                ->conversion('small')
                ->limit(1)
                ->square()
                ->label(''),
            Tables\Columns\TextColumn::make('name')
                ->label('Name')
                ->searchable(),
            Tables\Columns\TextColumn::make('location')
                ->label('Location')
                ->badge(),
            Tables\Columns\TextColumn::make('special_value')
                ->label('Special value')
                ->toggleable()
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('collection_name')
                ->label('Category')
                ->state(function (Model $record): ?string {
                    /** @var \Lunar\Models\Banner $record */
                    return $record->collection?->translateAttribute('name');
                })
                ->toggleable(),
            Tables\Columns\IconColumn::make('is_active')
                ->label('Active')
                ->boolean(),
            Tables\Columns\TextColumn::make('starts_at')
                ->label('Starts at')
                ->dateTime(),
            Tables\Columns\TextColumn::make('ends_at')
                ->label('Ends at')
                ->dateTime(),
        ];
    }

    public static function getDefaultRelations(): array
    {
        return [];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListBanners::route('/'),
            'create' => Pages\CreateBanner::route('/create'),
            'edit' => Pages\EditBanner::route('/{record}/edit'),
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('position');
    }
}


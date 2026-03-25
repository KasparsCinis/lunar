<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\DevelopmentUpdateResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Enums\DevelopmentUpdateKind;
use Lunar\Enums\DevelopmentUpdatePriority;
use Lunar\Enums\DevelopmentUpdateStatus;
use Lunar\Models\Contracts\DevelopmentUpdate as DevelopmentUpdateContract;

class DevelopmentUpdateResource extends BaseResource
{
    protected static ?string $permission = 'settings';

    protected static ?string $model = DevelopmentUpdateContract::class;

    protected static ?int $navigationSort = 15;

    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    public static function getLabel(): string
    {
        return 'Update';
    }

    public static function getPluralLabel(): string
    {
        return 'Updates';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Development';
    }

    public static function getDefaultForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('body')
                            ->label('Description')
                            ->rows(6)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('kind')
                            ->label('Type')
                            ->options(collect(DevelopmentUpdateKind::cases())->mapWithKeys(
                                fn (DevelopmentUpdateKind $k) => [$k->value => $k->label()]
                            ))
                            ->default(DevelopmentUpdateKind::FeatureRequest->value)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(collect(DevelopmentUpdateStatus::cases())->mapWithKeys(
                                fn (DevelopmentUpdateStatus $s) => [$s->value => $s->label()]
                            ))
                            ->default(DevelopmentUpdateStatus::New->value)
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->options(collect(DevelopmentUpdatePriority::cases())->mapWithKeys(
                                fn (DevelopmentUpdatePriority $p) => [$p->value => $p->label()]
                            ))
                            ->default(DevelopmentUpdatePriority::Medium->value)
                            ->required()
                            ->native(false),
                        Forms\Components\TextInput::make('reference_url')
                            ->label('Reference URL')
                            ->url()
                            ->maxLength(2048)
                            ->helperText('Optional link to a ticket, spec, or discussion.')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first in the backlog.'),
                        SpatieMediaLibraryFileUpload::make('illustration')
                            ->label('Screenshot / attachment')
                            ->collection('illustration')
                            ->disk(config('media-library.disk_name'))
                            ->image()
                            ->maxSize(5120)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters(static::getTableFilters())
            ->actions(static::getTableActions())
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->deferLoading();
    }

    /**
     * @return array<int, Tables\Columns\Column>
     */
    protected static function getTableColumns(): array
    {
        return [
            SpatieMediaLibraryImageColumn::make('illustration')
                ->collection('illustration')
                ->conversion('small')
                ->limit(1)
                ->square()
                ->label(''),
            Tables\Columns\TextColumn::make('title')
                ->label('Title')
                ->searchable()
                ->wrap(),
            Tables\Columns\TextColumn::make('kind')
                ->label('Type')
                ->formatStateUsing(fn (?DevelopmentUpdateKind $state): string => $state?->label() ?? '')
                ->badge()
                ->color(fn (?DevelopmentUpdateKind $state): string => match ($state) {
                    DevelopmentUpdateKind::Bug => 'danger',
                    DevelopmentUpdateKind::FeatureRequest => 'info',
                    DevelopmentUpdateKind::Improvement => 'success',
                    DevelopmentUpdateKind::Chore => 'gray',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn (?DevelopmentUpdateStatus $state): string => $state?->label() ?? '')
                ->badge()
                ->color(fn (?DevelopmentUpdateStatus $state): string => match ($state) {
                    DevelopmentUpdateStatus::New => 'gray',
                    DevelopmentUpdateStatus::InProgress => 'warning',
                    DevelopmentUpdateStatus::Completed => 'success',
                    default => 'gray',
                }),
            Tables\Columns\TextColumn::make('priority')
                ->label('Priority')
                ->formatStateUsing(fn (?DevelopmentUpdatePriority $state): string => $state?->label() ?? '')
                ->badge()
                ->color(fn (?DevelopmentUpdatePriority $state): string => match ($state) {
                    DevelopmentUpdatePriority::High => 'danger',
                    DevelopmentUpdatePriority::Medium => 'warning',
                    DevelopmentUpdatePriority::Low => 'gray',
                    default => 'gray',
                })
                ->toggleable(),
            Tables\Columns\TextColumn::make('completed_at')
                ->label('Completed')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->label('Updated')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    /**
     * @return array<int, Tables\Filters\BaseFilter>
     */
    protected static function getTableFilters(): array
    {
        return [
            Tables\Filters\TernaryFilter::make('completed_visibility')
                ->label('Completed items')
                ->placeholder('All items')
                ->trueLabel('Completed only')
                ->falseLabel('Hide completed')
                ->queries(
                    true: fn (Builder $query) => $query->completed(),
                    false: fn (Builder $query) => $query->open(),
                    blank: fn (Builder $query) => $query,
                )
                ->default(false),
            Tables\Filters\SelectFilter::make('kind')
                ->label('Type')
                ->options(collect(DevelopmentUpdateKind::cases())->mapWithKeys(
                    fn (DevelopmentUpdateKind $k) => [$k->value => $k->label()]
                )),
            Tables\Filters\SelectFilter::make('status')
                ->label('Status')
                ->options(collect(DevelopmentUpdateStatus::cases())->mapWithKeys(
                    fn (DevelopmentUpdateStatus $s) => [$s->value => $s->label()]
                )),
            Tables\Filters\SelectFilter::make('priority')
                ->label('Priority')
                ->options(collect(DevelopmentUpdatePriority::cases())->mapWithKeys(
                    fn (DevelopmentUpdatePriority $p) => [$p->value => $p->label()]
                )),
        ];
    }

    /**
     * @return array<int, Tables\Actions\Action|Tables\Actions\ActionGroup>
     */
    protected static function getTableActions(): array
    {
        return [
            Tables\Actions\ActionGroup::make([
                Tables\Actions\Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->visible(fn (Model $record): bool => $record->status === DevelopmentUpdateStatus::New)
                    ->action(function (Model $record): void {
                        /** @var \Lunar\Models\DevelopmentUpdate $record */
                        $record->update(['status' => DevelopmentUpdateStatus::InProgress]);
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Mark complete')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Model $record): bool => $record->status !== DevelopmentUpdateStatus::Completed)
                    ->action(function (Model $record): void {
                        /** @var \Lunar\Models\DevelopmentUpdate $record */
                        $record->update(['status' => DevelopmentUpdateStatus::Completed]);
                    }),
                Tables\Actions\Action::make('reopen')
                    ->label('Reopen')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Model $record): bool => $record->status === DevelopmentUpdateStatus::Completed)
                    ->action(function (Model $record): void {
                        /** @var \Lunar\Models\DevelopmentUpdate $record */
                        $record->update(['status' => DevelopmentUpdateStatus::New]);
                    }),
            ]),
            Tables\Actions\EditAction::make(),
        ];
    }

    public static function getDefaultRelations(): array
    {
        return [];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListDevelopmentUpdates::route('/'),
            'create' => Pages\CreateDevelopmentUpdate::route('/create'),
            'edit' => Pages\EditDevelopmentUpdate::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'body',
        ];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->title;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->orderBy('sort_order');
    }
}

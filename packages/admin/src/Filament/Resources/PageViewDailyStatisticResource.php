<?php

namespace Lunar\Admin\Filament\Resources;

use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\PageViewDailyStatisticResource\Pages;
use Lunar\Admin\Models\PageViewDailyStatistic;
use Lunar\Admin\Support\Resources\BaseResource;

class PageViewDailyStatisticResource extends BaseResource
{
    protected static ?string $permission = 'settings';

    protected static ?string $model = PageViewDailyStatistic::class;

    protected static ?int $navigationSort = 2;

    public static function getLabel(): string
    {
        return __('lunarpanel::page_view_analytics.label');
    }

    public static function getPluralLabel(): string
    {
        return __('lunarpanel::page_view_analytics.plural_label');
    }

    public static function getNavigationIcon(): ?string
    {
        return FilamentIcon::resolve('lunar::page-analytics');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('lunarpanel::global.sections.settings');
    }

    public static function getDefaultForm(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function getDefaultTable(Table $table): Table
    {
        return $table
            ->columns(static::getTableColumns())
            ->filters([
                //
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('stat_date', 'desc');
    }

    protected static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('stat_date')
                ->label(__('lunarpanel::page_view_analytics.table.stat_date'))
                ->date(),
            Tables\Columns\TextColumn::make('unique_visitors')
                ->label(__('lunarpanel::page_view_analytics.table.unique_visitors'))
                ->numeric(),
            Tables\Columns\TextColumn::make('authenticated_visitors')
                ->label(__('lunarpanel::page_view_analytics.table.authenticated_visitors'))
                ->numeric(),
            Tables\Columns\TextColumn::make('total_views')
                ->label(__('lunarpanel::page_view_analytics.table.total_views'))
                ->numeric(),
            Tables\Columns\TextColumn::make('avg_session_seconds')
                ->label(__('lunarpanel::page_view_analytics.table.avg_session_seconds'))
                ->formatStateUsing(fn (int $state): string => static::formatDuration($state)),
        ];
    }

    protected static function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return sprintf('%dm %02ds', $m, $s);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListPageViewDailyStatistics::route('/'),
        ];
    }
}

<?php

namespace Lunar\Admin\Filament\Resources\PageViewDailyStatisticResource\Pages;

use Filament\Support\Enums\MaxWidth;
use Lunar\Admin\Filament\Resources\PageViewDailyStatisticResource;
use Lunar\Admin\Filament\Widgets\PageAnalytics\PageAnalyticsOverview;
use Lunar\Admin\Filament\Widgets\PageAnalytics\PageAverageSessionChart;
use Lunar\Admin\Filament\Widgets\PageAnalytics\PageMostVisitedPathsChart;
use Lunar\Admin\Filament\Widgets\PageAnalytics\PageTrafficChart;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListPageViewDailyStatistics extends BaseListRecords
{
    protected static string $resource = PageViewDailyStatisticResource::class;

    protected function getDefaultHeaderWidgets(): array
    {
        return [
            PageAnalyticsOverview::class,
            PageTrafficChart::class,
            PageAverageSessionChart::class,
            PageMostVisitedPathsChart::class,
        ];
    }

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }
}

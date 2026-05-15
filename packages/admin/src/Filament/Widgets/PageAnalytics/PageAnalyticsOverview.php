<?php

namespace Lunar\Admin\Filament\Widgets\PageAnalytics;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Lunar\Admin\Models\PageViewDailyStatistic;

class PageAnalyticsOverview extends BaseWidget
{
    protected static ?string $pollingInterval = '120s';

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $days = max(1, (int) config('lunar.panel.page_view_analytics.chart_days', 30));
        $from = now()->subDays($days - 1)->startOfDay();

        $stats = PageViewDailyStatistic::query()
            ->where('stat_date', '>=', $from->toDateString())
            ->get();

        $totalViews = (int) $stats->sum('total_views');
        $avgDailyVisitors = (int) round($stats->avg('unique_visitors') ?? 0);

        $weightedNumerator = $stats->sum(
            fn (PageViewDailyStatistic $row) => $row->avg_session_seconds * $row->unique_visitors
        );
        $weightedDenominator = (int) $stats->sum('unique_visitors');
        $weightedSession = $weightedDenominator > 0
            ? (int) round($weightedNumerator / $weightedDenominator)
            : 0;

        return [
            Stat::make(
                __('lunarpanel::page_view_analytics.widgets.overview.total_views.label'),
                number_format($totalViews),
            )->description(__('lunarpanel::page_view_analytics.widgets.overview.total_views.description')),
            Stat::make(
                __('lunarpanel::page_view_analytics.widgets.overview.avg_daily_visitors.label'),
                number_format($avgDailyVisitors),
            )->description(__('lunarpanel::page_view_analytics.widgets.overview.avg_daily_visitors.description')),
            Stat::make(
                __('lunarpanel::page_view_analytics.widgets.overview.avg_session.label'),
                $this->formatDuration($weightedSession),
            )->description(__('lunarpanel::page_view_analytics.widgets.overview.avg_session.description')),
        ];
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $m = intdiv($seconds, 60);
        $s = $seconds % 60;

        return sprintf('%dm %02ds', $m, $s);
    }
}

<?php

namespace Lunar\Admin\Filament\Widgets\PageAnalytics;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Lunar\Admin\Models\PageViewDailyStatistic;

class PageAverageSessionChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pageAverageSessionChart';

    protected static ?string $pollingInterval = '120s';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('lunarpanel::page_view_analytics.widgets.session.heading');
    }

    protected function getOptions(): array
    {
        $days = max(1, (int) config('lunar.panel.page_view_analytics.chart_days', 30));

        $rows = PageViewDailyStatistic::query()
            ->where('stat_date', '>=', now()->subDays($days - 1)->startOfDay()->toDateString())
            ->orderBy('stat_date')
            ->get()
            ->keyBy(fn (PageViewDailyStatistic $r) => $r->stat_date->toDateString());

        $labels = [];
        $seconds = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->startOfDay();
            $key = $d->toDateString();
            $labels[] = $d->format('M j');
            $row = $rows->get($key);
            $seconds[] = $row ? $row->avg_session_seconds : 0;
        }

        return [
            'chart' => [
                'type' => 'line',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'series' => [
                [
                    'name' => __('lunarpanel::page_view_analytics.widgets.session.series'),
                    'data' => $seconds,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
            ],
            'yaxis' => [
                'min' => 0,
                'decimalsInFloat' => 0,
                'title' => [
                    'text' => __('lunarpanel::page_view_analytics.widgets.session.series'),
                ],
            ],
        ];
    }
}

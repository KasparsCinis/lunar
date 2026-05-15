<?php

namespace Lunar\Admin\Filament\Widgets\PageAnalytics;

use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Lunar\Admin\Models\PageViewDailyStatistic;

class PageTrafficChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pageTrafficChart';

    protected static ?string $pollingInterval = '120s';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('lunarpanel::page_view_analytics.widgets.traffic.heading');
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
        $visitors = [];
        $views = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i)->startOfDay();
            $key = $d->toDateString();
            $labels[] = $d->format('M j');
            $row = $rows->get($key);
            $visitors[] = $row ? $row->unique_visitors : 0;
            $views[] = $row ? $row->total_views : 0;
        }

        return [
            'chart' => [
                'type' => 'area',
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
                    'name' => __('lunarpanel::page_view_analytics.widgets.traffic.unique_visitors'),
                    'data' => $visitors,
                ],
                [
                    'name' => __('lunarpanel::page_view_analytics.widgets.traffic.total_views'),
                    'data' => $views,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
            ],
            'yaxis' => [
                [
                    'seriesName' => __('lunarpanel::page_view_analytics.widgets.traffic.unique_visitors'),
                    'title' => [
                        'text' => __('lunarpanel::page_view_analytics.widgets.traffic.unique_visitors'),
                    ],
                    'min' => 0,
                    'decimalsInFloat' => 0,
                ],
                [
                    'seriesName' => __('lunarpanel::page_view_analytics.widgets.traffic.total_views'),
                    'opposite' => true,
                    'title' => [
                        'text' => __('lunarpanel::page_view_analytics.widgets.traffic.total_views'),
                    ],
                    'min' => 0,
                    'decimalsInFloat' => 0,
                ],
            ],
            'tooltip' => [
                'shared' => true,
            ],
        ];
    }
}

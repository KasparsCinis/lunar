<?php

namespace Lunar\Admin\Filament\Widgets\PageAnalytics;

use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;
use Lunar\Admin\Models\PageViewPathDailyStatistic;

class PageMostVisitedPathsChart extends ApexChartWidget
{
    protected static ?string $chartId = 'pageMostVisitedPathsChart';

    protected static ?string $pollingInterval = '120s';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    protected function getHeading(): ?string
    {
        return __('lunarpanel::page_view_analytics.widgets.paths.heading');
    }

    protected function getOptions(): array
    {
        $days = max(1, (int) config('lunar.panel.page_view_analytics.chart_days', 30));
        $from = now()->subDays($days - 1)->startOfDay()->toDateString();

        $top = PageViewPathDailyStatistic::query()
            ->where('stat_date', '>=', $from)
            ->select([
                'path',
                DB::raw('SUM(views) as total_views'),
            ])
            ->groupBy('path')
            ->orderByDesc(DB::raw('SUM(views)'))
            ->limit(15)
            ->get();

        if ($top->isEmpty()) {
            $labels = [__('lunarpanel::page_view_analytics.widgets.paths.empty')];
            $data = [0];
        } else {
            $labels = $top->pluck('path')->map(fn (string $path) => mb_strlen($path) > 48 ? mb_substr($path, 0, 45).'…' : $path)->all();
            $data = $top->pluck('total_views')->map(fn ($v) => (int) $v)->all();
        }

        return [
            'chart' => [
                'type' => 'bar',
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'plotOptions' => [
                'bar' => [
                    'horizontal' => true,
                    'borderRadius' => 4,
                ],
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'series' => [
                [
                    'name' => __('lunarpanel::page_view_analytics.widgets.paths.series'),
                    'data' => $data,
                ],
            ],
            'xaxis' => [
                'categories' => $labels,
            ],
            'yaxis' => [
                'min' => 0,
                'decimalsInFloat' => 0,
            ],
        ];
    }
}

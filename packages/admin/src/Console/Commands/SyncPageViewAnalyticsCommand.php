<?php

namespace Lunar\Admin\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Lunar\Admin\Services\PageViewAnalyticsAggregator;

class SyncPageViewAnalyticsCommand extends Command
{
    protected $signature = 'lunar:sync-page-view-analytics
                            {--date= : Date (Y-m-d) to aggregate; defaults to yesterday}
                            {--prune-only : Only prune old activity rows and rolled-up stats}';

    protected $description = 'Roll up storefront page_view activity into daily statistics and prune old raw logs';

    public function handle(PageViewAnalyticsAggregator $aggregator): int
    {
        if ($this->option('prune-only')) {
            $aggregator->pruneOldStatistics();
            $aggregator->pruneOldActivityLogs();
            $this->info('Pruned old page view statistics and activity log rows.');

            return self::SUCCESS;
        }

        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : now()->subDay()->startOfDay();

        $aggregator->syncDay($date);
        $this->info('Aggregated page view statistics for '.$date->toDateString().'.');

        $aggregator->pruneOldStatistics();
        $aggregator->pruneOldActivityLogs();
        $this->info('Pruned data older than '.config('lunar.panel.page_view_analytics.retention_days', 30).' days.');

        return self::SUCCESS;
    }
}

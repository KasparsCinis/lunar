<?php

namespace Lunar\Admin\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Lunar\Admin\Models\PageViewDailyStatistic;
use Lunar\Admin\Models\PageViewPathDailyStatistic;
use Spatie\Activitylog\Models\Activity;

class PageViewAnalyticsAggregator
{
    public function syncDay(Carbon $day): void
    {
        $day = $day->copy()->startOfDay();
        $logName = config('lunar.panel.page_view_analytics.log_name');

        PageViewDailyStatistic::query()->whereDate('stat_date', $day)->delete();
        PageViewPathDailyStatistic::query()->whereDate('stat_date', $day)->delete();

        $query = Activity::query()
            ->where('log_name', $logName)
            ->whereDate('created_at', $day);

        $byVisitor = [];
        $minSessionSeconds = max(0, (int) config('lunar.panel.page_view_analytics.min_session_seconds', 15));

        foreach ($query->cursor() as $activity) {
            /** @var Activity $activity */
            $props = $this->normalizeProperties($activity);

            $hash = $props['visitor_hash'] ?? null;
            if (! is_string($hash) || $hash === '') {
                continue;
            }

            $ts = $activity->created_at;
            if (! isset($byVisitor[$hash])) {
                $byVisitor[$hash] = [
                    'first' => $ts,
                    'last' => $ts,
                    'views' => 0,
                    'authenticated' => false,
                    'paths' => [],
                ];
            } else {
                if ($ts->lt($byVisitor[$hash]['first'])) {
                    $byVisitor[$hash]['first'] = $ts;
                }
                if ($ts->gt($byVisitor[$hash]['last'])) {
                    $byVisitor[$hash]['last'] = $ts;
                }
            }

            $path = (string) ($props['path'] ?? '');
            if ($path === '') {
                $path = '(unknown)';
            }
            $path = mb_substr($path, 0, 512);

            $byVisitor[$hash]['views']++;
            $byVisitor[$hash]['paths'][$path] = ($byVisitor[$hash]['paths'][$path] ?? 0) + 1;

            if (! empty($props['visitor_authenticated'])) {
                $byVisitor[$hash]['authenticated'] = true;
            }
        }

        $uniqueVisitors = 0;
        $authenticatedVisitors = 0;
        $totalViews = 0;
        $pathCounts = [];
        $sessionSeconds = [];

        foreach ($byVisitor as $slot) {
            // Carbon 3: diffInSeconds is signed (negative when $this > $other).
            $duration = (int) $slot['first']->diffInSeconds($slot['last']);

            if ($duration < $minSessionSeconds) {
                continue;
            }

            $uniqueVisitors++;
            $sessionSeconds[] = $duration;
            $totalViews += $slot['views'];

            if ($slot['authenticated']) {
                $authenticatedVisitors++;
            }

            foreach ($slot['paths'] as $path => $views) {
                $pathCounts[$path] = ($pathCounts[$path] ?? 0) + $views;
            }
        }

        $avgSession = $uniqueVisitors > 0
            ? (int) round(array_sum($sessionSeconds) / $uniqueVisitors)
            : 0;

        PageViewDailyStatistic::query()->create([
            'stat_date' => $day->toDateString(),
            'unique_visitors' => $uniqueVisitors,
            'authenticated_visitors' => $authenticatedVisitors,
            'total_views' => $totalViews,
            'avg_session_seconds' => $avgSession,
        ]);

        arsort($pathCounts);
        $topLimit = (int) config('lunar.panel.page_view_analytics.top_paths_per_day', 200);
        $topPaths = array_slice($pathCounts, 0, $topLimit, true);

        foreach ($topPaths as $path => $views) {
            PageViewPathDailyStatistic::query()->create([
                'stat_date' => $day->toDateString(),
                'path' => $path,
                'views' => $views,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeProperties(Activity $activity): array
    {
        $props = $activity->properties;

        if ($props instanceof Collection) {
            return $props->toArray();
        }

        return is_array($props) ? $props : [];
    }

    public function pruneOldStatistics(): void
    {
        $days = (int) config('lunar.panel.page_view_analytics.retention_days', 30);
        $cutoff = now()->subDays($days)->startOfDay();

        PageViewDailyStatistic::query()->where('stat_date', '<', $cutoff)->delete();
        PageViewPathDailyStatistic::query()->where('stat_date', '<', $cutoff)->delete();
    }

    public function pruneOldActivityLogs(): void
    {
        $days = (int) config('lunar.panel.page_view_analytics.retention_days', 30);
        $logName = config('lunar.panel.page_view_analytics.log_name');

        Activity::query()
            ->where('log_name', $logName)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}

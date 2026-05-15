<?php

use Carbon\Carbon;
use Lunar\Admin\Models\PageViewDailyStatistic;
use Lunar\Admin\Services\PageViewAnalyticsAggregator;
use Spatie\Activitylog\Models\Activity;

uses(Lunar\Tests\Admin\TestCase::class);

beforeEach(function () {
    config(['lunar.panel.page_view_analytics.log_name' => 'page_views']);
});

it('aggregates average session length from first to last page view per visitor', function () {
    $day = Carbon::parse('2026-05-10')->startOfDay();
    $hash = 'visitor-a';

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/first',
            'visitor_hash' => $hash,
        ],
        'created_at' => $day->copy()->addMinutes(10),
        'updated_at' => $day->copy()->addMinutes(10),
    ]);

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/second',
            'visitor_hash' => $hash,
        ],
        'created_at' => $day->copy()->addMinutes(15),
        'updated_at' => $day->copy()->addMinutes(15),
    ]);

    app(PageViewAnalyticsAggregator::class)->syncDay($day);

    $stat = PageViewDailyStatistic::query()->whereDate('stat_date', $day)->first();

    expect($stat)->not->toBeNull()
        ->and($stat->unique_visitors)->toBe(1)
        ->and($stat->avg_session_seconds)->toBe(300);
});

it('records zero session length for a single page view', function () {
    $day = Carbon::parse('2026-05-11')->startOfDay();

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/only',
            'visitor_hash' => 'visitor-b',
        ],
        'created_at' => $day->copy()->addHour(),
        'updated_at' => $day->copy()->addHour(),
    ]);

    app(PageViewAnalyticsAggregator::class)->syncDay($day);

    $stat = PageViewDailyStatistic::query()->whereDate('stat_date', $day)->first();

    expect($stat->avg_session_seconds)->toBe(0);
});

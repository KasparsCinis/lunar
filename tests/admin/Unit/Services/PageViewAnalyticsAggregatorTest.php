<?php

use Carbon\Carbon;
use Lunar\Admin\Models\PageViewDailyStatistic;
use Lunar\Admin\Services\PageViewAnalyticsAggregator;
use Spatie\Activitylog\Models\Activity;

uses(Lunar\Tests\Admin\TestCase::class);

beforeEach(function () {
    config(['lunar.panel.page_view_analytics.log_name' => 'page_views']);
    config(['lunar.panel.page_view_analytics.min_session_seconds' => 15]);
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

it('ignores sessions shorter than configured minimum duration', function () {
    $day = Carbon::parse('2026-05-12')->startOfDay();

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/short-1',
            'visitor_hash' => 'visitor-short',
            'visitor_authenticated' => true,
        ],
        'created_at' => $day->copy()->addMinutes(1),
        'updated_at' => $day->copy()->addMinutes(1),
    ]);

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/short-2',
            'visitor_hash' => 'visitor-short',
            'visitor_authenticated' => true,
        ],
        'created_at' => $day->copy()->addMinutes(1)->addSeconds(10),
        'updated_at' => $day->copy()->addMinutes(1)->addSeconds(10),
    ]);

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/long-1',
            'visitor_hash' => 'visitor-long',
        ],
        'created_at' => $day->copy()->addMinutes(2),
        'updated_at' => $day->copy()->addMinutes(2),
    ]);

    Activity::query()->create([
        'log_name' => 'page_views',
        'description' => 'page_view',
        'properties' => [
            'path' => '/long-2',
            'visitor_hash' => 'visitor-long',
        ],
        'created_at' => $day->copy()->addMinutes(2)->addSeconds(30),
        'updated_at' => $day->copy()->addMinutes(2)->addSeconds(30),
    ]);

    app(PageViewAnalyticsAggregator::class)->syncDay($day);

    $stat = PageViewDailyStatistic::query()->whereDate('stat_date', $day)->first();

    expect($stat)->not->toBeNull()
        ->and($stat->unique_visitors)->toBe(1)
        ->and($stat->authenticated_visitors)->toBe(0)
        ->and($stat->total_views)->toBe(2)
        ->and($stat->avg_session_seconds)->toBe(30);
});

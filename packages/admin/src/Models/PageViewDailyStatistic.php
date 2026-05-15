<?php

namespace Lunar\Admin\Models;

use Lunar\Base\BaseModel;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $stat_date
 * @property int $unique_visitors
 * @property int $authenticated_visitors
 * @property int $total_views
 * @property int $avg_session_seconds
 */
class PageViewDailyStatistic extends BaseModel
{
    protected $table = 'page_view_daily_statistics';

    protected $fillable = [
        'stat_date',
        'unique_visitors',
        'authenticated_visitors',
        'total_views',
        'avg_session_seconds',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'unique_visitors' => 'integer',
        'authenticated_visitors' => 'integer',
        'total_views' => 'integer',
        'avg_session_seconds' => 'integer',
    ];
}

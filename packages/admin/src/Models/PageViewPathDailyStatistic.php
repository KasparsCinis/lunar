<?php

namespace Lunar\Admin\Models;

use Lunar\Base\BaseModel;

/**
 * @property int $id
 * @property \Illuminate\Support\Carbon $stat_date
 * @property string $path
 * @property int $views
 */
class PageViewPathDailyStatistic extends BaseModel
{
    protected $table = 'page_view_path_daily_statistics';

    protected $fillable = [
        'stat_date',
        'path',
        'views',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'views' => 'integer',
    ];
}

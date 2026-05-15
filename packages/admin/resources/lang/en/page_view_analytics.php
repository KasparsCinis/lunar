<?php

return [
    'label' => 'Page analytics',
    'plural_label' => 'Page analytics',

    'table' => [
        'stat_date' => 'Date',
        'unique_visitors' => 'Unique visitors',
        'authenticated_visitors' => 'Authenticated visitors',
        'total_views' => 'Page views',
        'avg_session_seconds' => 'Avg. session length',
    ],

    'widgets' => [
        'overview' => [
            'total_views' => [
                'label' => 'Page views (period)',
                'description' => 'Sum of all tracked page views',
            ],
            'avg_daily_visitors' => [
                'label' => 'Avg. daily visitors',
                'description' => 'Mean unique visitors per day',
            ],
            'avg_session' => [
                'label' => 'Avg. session length (period)',
                'description' => 'Weighted by daily visitor counts',
            ],
        ],
        'traffic' => [
            'heading' => 'Visitors and page views',
            'unique_visitors' => 'Unique visitors',
            'total_views' => 'Page views',
        ],
        'session' => [
            'heading' => 'Average session length',
            'series' => 'Avg. seconds',
        ],
        'paths' => [
            'heading' => 'Most visited paths',
            'series' => 'Views',
            'empty' => 'No path data yet',
        ],
    ],
];

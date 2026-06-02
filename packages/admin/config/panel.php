<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Variants
    |--------------------------------------------------------------------------
    |
    | When `true` this will show the Variants manager when editing a product. If your
    | storefront doesn't support variants, set this to false.
    |
    */
    'enable_variants' => true,

    /*
    |--------------------------------------------------------------------------
    | PDF Streaming
    |--------------------------------------------------------------------------
    |
    | When handling PDF's in the panel, you can decide whether to stream the PDF in
    | a new tab or download the PDF to your hard drive.
    |
    | Available options are 'download' or 'stream'
    |
    */
    'pdf_rendering' => 'download',

    /*
    |--------------------------------------------------------------------------
    | Enable Scout when searching on supported models.
    |--------------------------------------------------------------------------
    |
    | Some models in the core have Scout implemented as a search driver, if you
    | want to use Scout when possible on tables in the panel, enable it here.
    |
    */
    'scout_enabled' => false,

    /*
    |--------------------------------------------------------------------------
    | Navigation counts
    |--------------------------------------------------------------------------
    |
    | The admin panel will show a count of orders in the left navigation.
    | This is based upon specific order statuses. You can define the statuses
    | to include in the count below.
    |
    */
    'order_count_statuses' => ['payment-received'],

    /*
    |--------------------------------------------------------------------------
    | Bosch product feed (XML)
    |--------------------------------------------------------------------------
    |
    | Used by the "Sync Bosch" action on the products list. POSTs to BOSCH_API_URL
    | with form body: RequestFrom, RequestType=Products, Passcode, RequestParameters=PT.
    | Set in .env: BOSCH_API_URL, BOSCH_API_REQUESTFROM, BOSCH_API_PASSCODE
    |
    */
    'bosch' => [
        'api_url' => env('BOSCH_API_URL'),
        'request_from' => env('BOSCH_API_REQUESTFROM'),
        'passcode' => env('BOSCH_API_PASSCODE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Page view analytics (activity_log)
    |--------------------------------------------------------------------------
    |
    | Nightly job rolls up `activity_log` rows into lunar_page_view_* tables.
    | Configure the Spatie log_name used when recording storefront page views.
    |
    */
    'page_view_analytics' => [
        'log_name' => env('LUNAR_PAGE_VIEWS_LOG_NAME', 'page_views'),
        'retention_days' => (int) env('LUNAR_PAGE_VIEWS_RETENTION_DAYS', 30),
        'chart_days' => (int) env('LUNAR_PAGE_VIEWS_CHART_DAYS', 30),
        'top_paths_per_day' => (int) env('LUNAR_PAGE_VIEWS_TOP_PATHS', 200),
        'min_session_seconds' => (int) env('LUNAR_PAGE_VIEWS_MIN_SESSION_SECONDS', 15),
    ],

];


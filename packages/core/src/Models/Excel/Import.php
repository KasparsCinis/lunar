<?php

namespace Lunar\Models\Excel;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property integer  $id
 * @property integer  $collection_id
 * @property array    $column_mapping
 * @property integer  $status
 * @property string   $progress
 * @property string   $created_at
 * @property string   $updated_at
 *
 * @mixin Builder
 */
class Import extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    const STATUS_PENDING = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_ERROR = 3;
    const STATUS_SUCCESS = 4;

    const TYPE_COLLECTION = 1;
    const TYPE_INVENTORY = 2;

    protected $table = 'imports';
    protected $fillable = [
        'collection_id',
        'column_mapping',
        'status',
        'progress',
    ];

    protected $casts = [
        'column_mapping' => 'array',
    ];
}

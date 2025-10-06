<?php

namespace Lunar\Models\Excel;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;

/**
 * @property integer  $id
 * @property integer  $collection_id
 * @property array    $column_mapping
 * @property string   $created_at
 * @property string   $updated_at
 *
 * @mixin Builder
 */
class Import extends BaseModel
{
    protected $table = 'imports';
    protected $fillable = [
        'collection_id',
        'column_mapping',
    ];

    protected $casts = [
        'column_mapping' => 'array',
    ];
}

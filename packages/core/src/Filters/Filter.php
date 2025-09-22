<?php

namespace App\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;

/**
 * @property integer  $id
 * @property integer  $collection_id
 * @property integer  $type
 * @property string   $created_at
 * @property string   $updated_at
 *
 * @mixin Builder
 */
class Filter extends BaseModel
{
    protected $table = 'filters';
    protected $fillable = [
        'collection_id',
        'type',
    ];
}

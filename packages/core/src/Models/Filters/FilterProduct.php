<?php

namespace Lunar\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;

/**
 * @property integer  $id
 * @property integer  $filter_id
 * @property integer  $product_id
 * @property string   $value
 * @property string   $created_at
 * @property string   $updated_at
 *
 * @mixin Builder
 */
class FilterProduct extends BaseModel
{
    protected $table = 'filters_product';
    protected $fillable = [
        'filter_id',
        'product_id',
        'value',
    ];
}

<?php

namespace Lunar\Models\Filters;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasOne;
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

    public function product(): HasOne
    {
        return $this->hasOne(Product::modelClass(), 'id', 'product_id');
    }
}

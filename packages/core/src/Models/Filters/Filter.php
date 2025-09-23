<?php

namespace Lunar\Models\Filters;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;
use Lunar\Base\Casts\AsAttributeData;
use Lunar\Base\Traits\HasTranslations;

/**
 * @property integer  $id
 * @property integer  $collection_id
 * @property ?\Illuminate\Support\Collection $attribute_data
 * @property integer  $type
 * @property string   $created_at
 * @property string   $updated_at
 *
 * @mixin Builder
 */
class Filter extends BaseModel
{
    use HasTranslations;

    const TYPE_DROPDOWN = 1;

    protected $table = 'filters';
    protected $fillable = [
        'attribute_data',
        'collection_id',
        'type',
    ];

    protected $casts = [
        'attribute_data' => AsAttributeData::class,
    ];
}

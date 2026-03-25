<?php

namespace Lunar\Models;

use Illuminate\Database\Eloquent\Builder;
use Lunar\Base\BaseModel;
use Lunar\Enums\DevelopmentUpdateKind;
use Lunar\Enums\DevelopmentUpdatePriority;
use Lunar\Enums\DevelopmentUpdateStatus;
use Lunar\Models\Contracts\DevelopmentUpdate as DevelopmentUpdateContract;
use Spatie\Image\Enums\BorderType;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $title
 * @property string|null $body
 * @property DevelopmentUpdateKind $kind
 * @property DevelopmentUpdateStatus $status
 * @property DevelopmentUpdatePriority $priority
 * @property string|null $reference_url
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property int|null $created_by_staff_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class DevelopmentUpdate extends BaseModel implements DevelopmentUpdateContract, HasMedia
{
    use InteractsWithMedia;

    /**
     * {@inheritDoc}
     */
    protected $guarded = [];

    /**
     * {@inheritDoc}
     */
    protected $casts = [
        'kind' => DevelopmentUpdateKind::class,
        'status' => DevelopmentUpdateStatus::class,
        'priority' => DevelopmentUpdatePriority::class,
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model): void {
            if ($model->status === DevelopmentUpdateStatus::Completed) {
                $model->completed_at ??= now();
            } else {
                $model->completed_at = null;
            }
        });
    }

    /**
     * Open items (not completed), ordered for backlog views.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', '!=', DevelopmentUpdateStatus::Completed->value);
    }

    /**
     * Completed items only.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', DevelopmentUpdateStatus::Completed->value);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('illustration')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('small')
            ->fit(Fit::Fill, 300, 300)
            ->border(0, BorderType::Overlay, color: '#FFF')
            ->background('#FFF')
            ->sharpen(10)
            ->keepOriginalImageFormat()
            ->nonQueued();
    }
}

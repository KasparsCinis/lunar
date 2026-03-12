<?php

namespace Lunar\Admin\Filament\Resources\BannerResource\Pages;

use Lunar\Admin\Filament\Resources\BannerResource;
use Lunar\Admin\Support\Pages\BaseCreateRecord;

class CreateBanner extends BaseCreateRecord
{
    protected static string $resource = BannerResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}


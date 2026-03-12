<?php

namespace Lunar\Admin\Filament\Resources\BannerResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\BannerResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListBanners extends BaseListRecords
{
    protected static string $resource = BannerResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}


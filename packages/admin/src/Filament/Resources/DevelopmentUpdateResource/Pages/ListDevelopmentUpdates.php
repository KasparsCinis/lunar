<?php

namespace Lunar\Admin\Filament\Resources\DevelopmentUpdateResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\DevelopmentUpdateResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListDevelopmentUpdates extends BaseListRecords
{
    protected static string $resource = DevelopmentUpdateResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

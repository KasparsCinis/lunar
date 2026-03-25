<?php

namespace Lunar\Admin\Filament\Resources\DevelopmentUpdateResource\Pages;

use Lunar\Admin\Filament\Resources\DevelopmentUpdateResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditDevelopmentUpdate extends BaseEditRecord
{
    protected static string $resource = DevelopmentUpdateResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

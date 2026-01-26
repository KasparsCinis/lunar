<?php

namespace Lunar\Admin\Filament\Resources\ImportResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\ImportResource;
use Lunar\Admin\Filament\Resources\LanguageResource;
use Lunar\Admin\Support\Pages\BaseListRecords;

class ListImports extends BaseListRecords
{
    protected static string $resource = ImportResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }
}

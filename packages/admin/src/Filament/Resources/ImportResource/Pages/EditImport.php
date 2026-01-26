<?php

namespace Lunar\Admin\Filament\Resources\ImportResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\ImportResource;
use Lunar\Admin\Filament\Resources\LanguageResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditImport extends BaseEditRecord
{
    protected static string $resource = ImportResource::class;
    protected static string $view = 'lunarpanel::resources.import-resource.pages.edit-import';

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

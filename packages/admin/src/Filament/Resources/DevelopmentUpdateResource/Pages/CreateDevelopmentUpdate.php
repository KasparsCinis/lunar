<?php

namespace Lunar\Admin\Filament\Resources\DevelopmentUpdateResource\Pages;

use Filament\Facades\Filament;
use Lunar\Admin\Filament\Resources\DevelopmentUpdateResource;
use Lunar\Admin\Support\Pages\BaseCreateRecord;

class CreateDevelopmentUpdate extends BaseCreateRecord
{
    protected static string $resource = DevelopmentUpdateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        $user = Filament::auth()->user();
        if ($user !== null) {
            $data['created_by_staff_id'] = $user->getAuthIdentifier();
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

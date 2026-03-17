<?php

namespace Lunar\Admin\Filament\Resources\CustomerResource\Pages;

use Filament\Actions;
use Filament\Actions\ForceDeleteAction;
use Lunar\Admin\Filament\Resources\CustomerResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditCustomer extends BaseEditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            ForceDeleteAction::make()->databaseTransaction(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

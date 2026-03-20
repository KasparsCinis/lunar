<?php

namespace Lunar\Admin\Filament\Resources\BannerResource\Pages;

use Filament\Actions;
use Lunar\Admin\Filament\Resources\BannerResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class EditBanner extends BaseEditRecord
{
    protected static string $resource = BannerResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = parent::mutateFormDataBeforeSave($data);

        if (($data['location'] ?? null) === 'homepage') {
            $data['collection_id'] = null;
        }

        return $data;
    }

    public function getTitle(): string
    {
        return 'Edit banner';
    }

    protected function getDefaultHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}


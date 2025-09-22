<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Pages\BaseEditRecord;

class ManageProductFilters extends BaseEditRecord
{
    protected static string $resource = ProductResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Filters';
    }

    public static function getNavigationLabel(): string
    {
        return 'Filters';
    }

    public function getBreadcrumb(): string
    {
        return 'Filters';
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-funnel';
    }

    protected function getDefaultHeaderActions(): array
    {
        return [];
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}

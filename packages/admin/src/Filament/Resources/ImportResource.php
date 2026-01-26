<?php

namespace Lunar\Admin\Filament\Resources;

use Awcodes\FilamentBadgeableColumn\Components\Badge;
use Awcodes\FilamentBadgeableColumn\Components\BadgeableColumn;
use Filament\Forms;
use Filament\Forms\Components\Component;
use Filament\Support\Facades\FilamentIcon;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Lunar\Admin\Filament\Resources\ImportResource\Pages;
use Lunar\Admin\Support\Resources\BaseResource;
use Lunar\Models\Excel\Import;

class ImportResource extends BaseResource
{
    protected static ?string $permission = 'catalog:manage-products';

    protected static ?string $model = Import::class;

    protected static bool $shouldRegisterNavigation = false;

    public static function getLabel(): string
    {
        return 'Import';
    }

    public static function getPluralLabel(): string
    {
        return 'Imports';
    }

    public static function getDefaultPages(): array
    {
        return [
            'index' => Pages\ListImports::route('/'),
            'edit' => Pages\EditImport::route('/{record}/edit'),
        ];
    }
}

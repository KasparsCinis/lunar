<?php

namespace Lunar\Admin\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Support\Exceptions\Halt;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Lunar\Admin\Filament\Resources\ProductResource;
use Lunar\Admin\Support\Forms\Components\Attributes;
use Lunar\Admin\Support\Pages\BaseEditRecord;
use Lunar\Models\Filters\Filter;
use Lunar\Models\Filters\FilterProduct;

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

    private function getFilters() {
        $record = $this->getRecord();
        $allCollectionIds = [];

        foreach ($record->collections as $attachedCollection) {
            $collection = $attachedCollection;

            $allCollectionIds = [$collection->id];

            while ($collection->parent) {
                $collection = $collection->parent;
                $allCollectionIds[] = $collection->id;
            }
        }

        $fields = [];
        return Filter::whereIn('collection_id', $allCollectionIds)
            ->get();
    }

    public function form(Form $form): Form
    {
        $record = $this->getRecord();
        $filters = $this->getFilters();

        foreach ($filters as $filter) {
            $existingValue = FilterProduct::where('product_id', $record->id)
                ->where('filter_id', $filter->id)
                ->first();

            $fields[] = Forms\Components\TextInput::make("filter-{$filter->id}")
                ->placeholder($existingValue?->value)
                ->label($filter->translateAttribute('name'));
        }

        return $form->schema($fields);
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        $record = $this->getRecord();
        $filters = $this->getFilters();

        try {
            DB::beginTransaction();

            foreach ($filters as $filter) {
                $existingValue = FilterProduct::where('product_id', $record->id)
                    ->where('filter_id', $filter->id)
                    ->first();
                $value = isset($this->data["filter-{$filter->id}"]) ? $this->data["filter-{$filter->id}"] : null;

                if ($existingValue) {
                    $existingValue->update([
                        'value' => $value
                    ]);
                } else {
                    FilterProduct::create([
                        'product_id' => $record->id,
                        'filter_id' => $filter->id,
                        'value' => $value
                    ]);
                }
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
        }

        $this->getSavedNotification()?->send();
    }
}

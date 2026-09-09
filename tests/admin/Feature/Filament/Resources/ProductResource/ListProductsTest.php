<?php

uses(\Lunar\Tests\Admin\Feature\Filament\TestCase::class)
    ->group('resource.product');

it('can create product', function () {
    \Lunar\Models\Attribute::factory()->create([
        'type' => \Lunar\FieldTypes\TranslatedText::class,
        'attribute_type' => 'product',
        'handle' => 'name',
        'name' => [
            'en' => 'Name',
        ],
        'description' => [
            'en' => 'Description',
        ],
    ]);
    \Lunar\Models\TaxClass::factory()->create([
        'default' => true,
    ]);
    \Lunar\Models\Currency::factory()->create([
        'default' => true,
        'decimal_places' => 2,
    ]);
    $language = \Lunar\Models\Language::factory()->create([
        'default' => true,
    ]);

    $productType = \Lunar\Models\ProductType::factory()->create();

    $this->asStaff();

    \Livewire\Livewire::test(\Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts::class)
        ->callAction('create', data: [
            'name' => [$language->code => 'Foo Bar'],
            'base_price' => 10.99,
            'sku' => 'ABCABCAB',
            'product_type_id' => $productType->id,
        ])->assertHasNoActionErrors();

    \Pest\Laravel\assertDatabaseHas((new \Lunar\Models\Product)->getTable(), [
        'product_type_id' => $productType->id,
        'status' => 'draft',
        'attribute_data' => json_encode([
            'name' => [
                'field_type' => \Lunar\FieldTypes\TranslatedText::class,
                'value' => [
                    $language->code => 'Foo Bar',
                ],
            ],
        ]),
    ]);

    $this->assertDatabaseHas((new \Lunar\Models\ProductVariant)->getTable(), [
        'sku' => 'ABCABCAB',
    ]);

    $this->assertDatabaseHas((new \Lunar\Models\Price)->getTable(), [
        'price' => '1099',
    ]);
});

it('omits blank excel headers so filament select labels are never null', function () {
    expect(\Lunar\Admin\Filament\Resources\ProductResource\Pages\ListProducts::excelHeaderSelectOptions([
        'SKU',
        null,
        'Stock',
        '',
        '  Price  ',
        2024,
    ]))->toBe([
        0 => 'SKU',
        2 => 'Stock',
        4 => 'Price',
        5 => '2024',
    ]);
});

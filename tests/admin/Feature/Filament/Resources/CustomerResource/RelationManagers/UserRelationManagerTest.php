<?php

use Livewire\Livewire;
use Lunar\Admin\Filament\Resources\CustomerResource\Pages\EditCustomer;
use Lunar\Admin\Filament\Resources\CustomerResource\RelationManagers\UserRelationManager;
use Lunar\Models\Customer;
use Lunar\Tests\Admin\Stubs\User;

uses(\Lunar\Tests\Admin\Feature\Filament\TestCase::class)
    ->group('resource.customer');

it('can show user phone in the table', function () {
    $this->asStaff();

    $customer = Customer::factory()->create();
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'phone' => '+37120000000',
    ]);
    $customer->users()->attach($user);

    Livewire::test(UserRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->assertSuccessful()
        ->assertTableColumnExists('phone')
        ->assertCanSeeTableRecords([$user])
        ->assertSee('+37120000000');
});

it('can save user phone', function () {
    $this->asStaff();

    $customer = Customer::factory()->create();
    $user = User::create([
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'phone' => '111',
    ]);
    $customer->users()->attach($user);

    Livewire::test(UserRelationManager::class, [
        'ownerRecord' => $customer,
        'pageClass' => EditCustomer::class,
    ])
        ->callTableAction('edit', $user, data: [
            'email' => $user->email,
            'phone' => '+37120000000',
        ])
        ->assertHasNoTableActionErrors();

    expect($user->refresh()->phone)->toBe('+37120000000');
});

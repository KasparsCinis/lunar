<?php

uses(\Lunar\Tests\Core\TestCase::class);
uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

use Lunar\Models\Currency;
use Lunar\Models\Customer;
use Lunar\Models\Language;
use Lunar\Models\Order;
use Lunar\Tests\Core\Stubs\User;

beforeEach(function () {
    Language::factory()->create([
        'default' => true,
        'code' => 'en',
    ]);

    Currency::factory()->create([
        'default' => true,
        'decimal_places' => 2,
    ]);
});

test('deleting customer removes user who had only that customer and nulls order user_id', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['email' => 'sole@example.test']);
    $customer->users()->attach($user);

    $order = Order::factory()->create([
        'user_id' => $user->getKey(),
        'customer_id' => $customer->getKey(),
    ]);

    $customer->delete();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
    expect($order->fresh()->user_id)->toBeNull();
});

test('deleting customer does not remove user linked to another customer', function () {
    $customerA = Customer::factory()->create();
    $customerB = Customer::factory()->create();
    $user = User::factory()->create(['email' => 'multi@example.test']);
    $customerA->users()->attach($user);
    $customerB->users()->attach($user);

    $customerA->delete();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();
});

test('force deleting customer removes sole user', function () {
    $customer = Customer::factory()->create();
    $user = User::factory()->create(['email' => 'force@example.test']);
    $customer->users()->attach($user);

    $customer->forceDelete();

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
});

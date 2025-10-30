<?php

namespace Lunar\Actions\Carts;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Lunar\Actions\AbstractAction;
use Lunar\Exceptions\DisallowMultipleCartOrdersException;
use Lunar\Facades\DB;
use Lunar\Jobs\Orders\MarkAsNewCustomer;
use Lunar\Models\Address;
use Lunar\Models\Cart;
use Lunar\Models\Contracts\Cart as CartContract;
use Lunar\Models\Contracts\Order as OrderContract;
use Lunar\Models\Customer;
use Lunar\Models\Order;

final class CreateOrder extends AbstractAction
{
    /**
     * Execute the action.
     */
    public function execute(
        CartContract $cart,
        bool $allowMultipleOrders = false,
        ?int $orderIdToUpdate = null
    ): self {
        $this->passThrough = DB::transaction(function () use ($cart, $allowMultipleOrders, $orderIdToUpdate) {
            /** @var Order $order */
            /** @var Cart $cart */
            $order = $cart->draftOrder($orderIdToUpdate)->first() ?: App::make(OrderContract::class);

            if ($cart->hasCompletedOrders() && ! $allowMultipleOrders) {
                throw new DisallowMultipleCartOrdersException;
            }

            $order->fill([
                'cart_id' => $cart->id,
                'fingerprint' => $cart->fingerprint(),
                'placed_at' => now()
            ]);

            $order = app(Pipeline::class)
                ->send($order)
                ->through(
                    config('lunar.orders.pipelines.creation', [])
                )->thenReturn(function ($order) {
                    return $order;
                });

            $cart->discounts?->each(function ($discount) use ($cart) {
                $discount->markAsUsed($cart)->discount->save();
            });

            $cart->save();

            MarkAsNewCustomer::dispatch($order->id);

            $order->refresh();

            /**
             * Custom adjustments
             * - create customer account for the user
             * - create shipping address for the user
             * - create billing address for the user
             */
            $user = Auth::user();

            if ($user) {
                $customer = $user->customers()->first();

                if (!$customer) {
                    $customer = Customer::create([
                        'first_name' => $cart->billingAddress?->first_name ?? '',
                        'last_name' => $cart->billingAddress?->last_name ?? '',
                    ]);
                    $customer->users()->attach($user);
                }

                $customer->fill([
                    'first_name' => $cart->billingAddress?->first_name ?? $customer->first_name,
                    'last_name' => $cart->billingAddress?->last_name ?? $customer->last_name,
                    'company_name' => $cart->billingAddress?->company_name ?? $customer->company_name,
                ])->save();

                $cartShippingData = $cart->shippingAddress;
                $cartBillingData = $cart->billingAddress;

                Address::where('customer_id', $customer->id)
                    ->update(['shipping_default' => 0]);
                Address::where('customer_id', $customer->id)
                    ->update(['billing_default' => 0]);

                $shippingAddress = Address::firstOrCreate([
                    'customer_id' => $customer->id,
                    'country_id' => $cartShippingData->country_id,
                    'first_name' => $cartShippingData->first_name,
                    'last_name' => $cartShippingData->last_name,
                    'company_name' => $cartShippingData->company_name,
                    'line_one' => $cartShippingData->line_one,
                    'city' => $cartShippingData->city,
                    'postcode' => $cartShippingData->postcode,
                    'contact_email' => $cartShippingData->contact_email,
                    'contact_phone' => $cartShippingData->contact_phone,
                ]);
                $billingAddress = Address::firstOrCreate([
                    'customer_id' => $customer->id,
                    'country_id' => $cartBillingData->country_id,
                    'first_name' => $cartBillingData->first_name,
                    'last_name' => $cartBillingData->last_name,
                    'company_name' => $cartBillingData->company_name,
                    'line_one' => $cartBillingData->line_one,
                    'city' => $cartBillingData->city,
                    'postcode' => $cartBillingData->postcode,
                    'contact_email' => $cartBillingData->contact_email,
                    'contact_phone' => $cartBillingData->contact_phone,
                ]);

                $shippingAddress->shipping_default = 1;
                $shippingAddress->saveOrFail();

                if ($billingAddress->id != $shippingAddress->id) {
                    $billingAddress->billing_default = 1;
                    $billingAddress->saveOrFail();
                }
            }

            return $order;
        });

        return $this;
    }
}

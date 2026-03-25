<?php

namespace Lunar\Actions\Customers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Lunar\Actions\AbstractAction;
use Lunar\Models\Cart;
use Lunar\Models\Contracts\Customer as CustomerContract;
use Lunar\Models\Order;

class RemoveUsersWhoOnlyBelongToCustomer extends AbstractAction
{
    /**
     * When a customer is deleted, remove linked login accounts that have no other customers.
     * Clears Lunar foreign keys so the users table row can be removed and the email reused.
     */
    public function execute(CustomerContract $customer): self
    {
        DB::transaction(function () use ($customer) {
            $customer->loadMissing('users');

            foreach ($customer->users as $user) {
                $otherCustomersCount = $user->customers()
                    ->whereKeyNot($customer->getKey())
                    ->count();

                if ($otherCustomersCount > 0) {
                    continue;
                }

                $this->releaseUserFromLunarOwnedRecords($user);

                $user->customers()->detach();

                if (in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
                    $user->forceDelete();
                } else {
                    $user->delete();
                }
            }
        });

        return $this;
    }

    protected function releaseUserFromLunarOwnedRecords(Model $user): void
    {
        $userId = $user->getKey();

        Order::query()->where('user_id', $userId)->update(['user_id' => null]);
        Cart::query()->withTrashed()->where('user_id', $userId)->update(['user_id' => null]);

        $discountUserTable = config('lunar.database.table_prefix').'discount_user';

        if (Schema::hasTable($discountUserTable)) {
            DB::table($discountUserTable)->where('user_id', $userId)->delete();
        }
    }
}

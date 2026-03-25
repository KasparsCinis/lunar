<?php

namespace Lunar\Observers;

use Lunar\Actions\Customers\RemoveUsersWhoOnlyBelongToCustomer;
use Lunar\Models\Contracts\Customer as CustomerContract;

class CustomerObserver
{
    /**
     * Handle the Customer "deleting" event.
     * Only run cleanup when force deleting to preserve relationships for soft-deleted customers.
     *
     * @return void
     */
    public function deleting(CustomerContract $customer)
    {
        RemoveUsersWhoOnlyBelongToCustomer::run($customer);

        if ($customer->isForceDeleting()) {
            $customer->customerGroups()->detach();
            $customer->discounts()->detach();
            $customer->users()->detach();
            $customer->addresses()->update(['customer_id' => null]);
            $customer->orders()->update(['customer_id' => null]);
            $customer->carts()->withTrashed()->update(['customer_id' => null]);
        }
    }
}

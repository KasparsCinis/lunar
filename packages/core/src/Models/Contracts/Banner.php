<?php

namespace Lunar\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

interface Banner
{
    /**
     * The collection (category) this banner is attached to, if any.
     */
    public function collection(): BelongsTo;
}


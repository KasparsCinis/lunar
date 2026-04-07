<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->integer('stock_zero_delay')->nullable()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->dropColumn('stock_zero_delay');
        });
    }
};

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;
use Lunar\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->string('stock_zero_delay')->nullable()->default('4-7')->change();
        });
    }

    public function down(): void
    {
        DB::table($this->prefix.'product_variants')
            ->whereNull('stock_zero_delay')
            ->update(['stock_zero_delay' => '4-7']);

        Schema::table($this->prefix.'product_variants', function (Blueprint $table) {
            $table->string('stock_zero_delay')->default('4-7')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'cart_addresses', function (Blueprint $table) {
            $table->string('tax_identifier_vat')->after('tax_identifier')->nullable();
            $table->string('company_address')->after('line_three')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'cart_addresses', function (Blueprint $table) {
            $table->dropColumn([
                'tax_identifier_vat',
                'company_address',
            ]);
        });
    }
};


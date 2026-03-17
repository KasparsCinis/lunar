<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'banners', function (Blueprint $table) {
            $table->string('type')->default('media')->after('location');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'banners', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

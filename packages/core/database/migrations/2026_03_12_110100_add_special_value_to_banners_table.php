<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->prefix.'banners', function (Blueprint $table) {
            $table->string('special_value')->nullable()->after('collection_id');
        });
    }

    public function down(): void
    {
        Schema::table($this->prefix.'banners', function (Blueprint $table) {
            $table->dropColumn('special_value');
        });
    }
};

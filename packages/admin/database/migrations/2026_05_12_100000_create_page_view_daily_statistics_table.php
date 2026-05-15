<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'page_view_daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date')->unique();
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->unsignedInteger('authenticated_visitors')->default(0);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedInteger('avg_session_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'page_view_daily_statistics');
    }
};

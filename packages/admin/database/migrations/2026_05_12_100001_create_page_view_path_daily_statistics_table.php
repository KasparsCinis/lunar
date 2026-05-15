<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'page_view_path_daily_statistics', function (Blueprint $table) {
            $table->id();
            $table->date('stat_date');
            $table->string('path', 512);
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamps();

            $table->unique(['stat_date', 'path']);
            $table->index('stat_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'page_view_path_daily_statistics');
    }
};

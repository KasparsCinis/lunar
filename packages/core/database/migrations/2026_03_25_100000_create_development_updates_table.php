<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'development_updates', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('kind')->index();
            $table->string('status')->index();
            $table->string('priority')->default('medium')->index();
            $table->string('reference_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->unsignedBigInteger('created_by_staff_id')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'development_updates');
    }
};

<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create($this->prefix.'banners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->index(); // e.g. homepage, category
            $table->foreignId('collection_id')
                ->nullable()
                ->constrained($this->prefix.'collections')
                ->nullOnDelete();
            $table->string('media_type')->default('image');
            $table->string('media_path')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'banners');
    }
};


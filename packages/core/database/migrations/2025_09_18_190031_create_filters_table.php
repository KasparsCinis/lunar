<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Lunar\Base\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create($this->prefix.'filters', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('collection_id')
                ->constrained('lunar_collections')
                ->onDelete('cascade');
            $table->jsonb('attribute_data')->nullable();
            $table->integer('type');
            $table->timestamps();
        });
        Schema::create($this->prefix.'filters_product', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('filter_id')
                ->constrained($this->prefix.'filters')
                ->onDelete('cascade');
            $table
                ->foreignId('product_id')
                ->constrained($this->prefix.'products')
                ->onDelete('cascade');
            $table->string('value', 250);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'filters_product');
        Schema::dropIfExists($this->prefix.'filters');
    }
};

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
        Schema::create($this->prefix.'imports', function (Blueprint $table) {
            $table->id();
            $table
                ->foreignId('collection_id')
                ->constrained('lunar_collections')
                ->onDelete('cascade');
            $table->jsonb('column_mapping')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->prefix.'imports');
    }
};

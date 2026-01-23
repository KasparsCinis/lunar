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
        Schema::table($this->prefix.'imports', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);

            $table->unsignedBigInteger('collection_id')
                ->nullable(true)
                ->change();

            $table->foreign('collection_id')
                ->references('id')
                ->on('lunar_collections')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table($this->prefix.'imports', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);

            $table->unsignedBigInteger('collection_id')
                ->nullable(false)
                ->change();

            $table->foreign('collection_id')
                ->references('id')
                ->on('lunar_collections')
                ->onDelete('cascade');
        });
    }
};

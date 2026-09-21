<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = $this->usersTable();

        if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'phone')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->string('phone')->nullable();
        });
    }

    public function down(): void
    {
        $tableName = $this->usersTable();

        if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'phone')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    private function usersTable(): string
    {
        $model = config('auth.providers.users.model');

        return $model && class_exists($model)
            ? (new $model)->getTable()
            : 'users';
    }
};

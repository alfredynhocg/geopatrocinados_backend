<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
        });

        Schema::table('permisos', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });

        Schema::table('permisos', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });
    }
};

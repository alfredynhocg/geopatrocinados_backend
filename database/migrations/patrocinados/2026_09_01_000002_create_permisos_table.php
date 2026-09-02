<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('permisos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 120)->unique();
            $table->string('modulo', 80);
            $table->string('accion', 80);
            $table->string('descripcion', 255)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->uuid('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permisos');
    }
};

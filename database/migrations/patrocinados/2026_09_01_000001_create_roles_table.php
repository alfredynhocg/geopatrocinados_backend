<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nombre', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->uuid('updated_by')->nullable();
            // FK a usuarios.id se agrega en 2026_09_01_000004, después de crear usuarios.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 80)->unique();
            $table->string('email', 180)->unique();
            $table->text('password_hash');
            $table->string('nombres', 100);
            $table->string('apellidos', 120);
            $table->string('telefono', 40)->nullable();
            $table->string('estado', 20)->default('ACTIVO');
            $table->smallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->timestampTz('ultimo_login_at')->nullable();
            $table->timestampTz('password_cambiado_at')->nullable();
            $table->timestampTz('created_at');
            $table->timestampTz('updated_at');
            $table->timestampTz('deleted_at')->nullable();
            $table->uuid('updated_by')->nullable();
        });

        // Auto-referencia: se agrega después de crear la tabla para poder declarar la FK sobre sí misma.
        Schema::table('usuarios', function (Blueprint $table) {
            $table->foreign('updated_by')->references('id')->on('usuarios')->onDelete('set null');
        });

        DB::statement("ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_estado CHECK (estado IN ('ACTIVO','INACTIVO','BLOQUEADO'))");
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('usuarios');
    }
};

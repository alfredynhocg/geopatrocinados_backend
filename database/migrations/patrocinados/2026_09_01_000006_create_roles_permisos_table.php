<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('roles_permisos', function (Blueprint $table) {
            $table->foreignUuid('rol_id')->constrained('roles')->onDelete('cascade');
            $table->foreignUuid('permiso_id')->constrained('permisos')->onDelete('cascade');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->primary(['rol_id', 'permiso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles_permisos');
    }
};

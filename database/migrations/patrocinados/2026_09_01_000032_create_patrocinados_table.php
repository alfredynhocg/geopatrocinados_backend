<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('patrocinados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('codigo', 60)->unique();
            $table->string('nombres', 120);
            $table->string('apellidos', 160)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('sexo', 30)->nullable();
            $table->foreignUuid('comunidad_id')->constrained('comunidades')->onDelete('restrict');
            $table->foreignUuid('ubicacion_id')->nullable()->constrained('ubicaciones')->onDelete('restrict');
            $table->string('unidad_educativa', 200)->nullable();
            $table->string('nivel_educativo', 120)->nullable();
            $table->foreignUuid('estado_id')->constrained('estados_patrocinados')->onDelete('restrict');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('comunidad_id');
            $table->index('estado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrocinados');
    }
};

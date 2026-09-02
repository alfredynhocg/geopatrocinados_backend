<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql_patrocinados';

    public function up(): void
    {
        Schema::create('fotos_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->text('clave_almacenamiento')->unique();
            $table->string('nombre_archivo', 255)->nullable();
            $table->string('tipo_archivo', 100);
            $table->bigInteger('tamanio');
            $table->integer('ancho')->nullable();
            $table->integer('alto')->nullable();
            $table->char('hash_sha256', 64);
            $table->timestampTz('fecha_captura')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('cifrada')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();

            $table->index('visita_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos_visitas');
    }
};

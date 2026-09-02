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
        Schema::create('ubicaciones_visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
            $table->foreignUuid('dispositivo_id')->constrained('dispositivos')->onDelete('restrict');
            $table->foreignUuid('tecnico_id')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_captura');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('precision_metros', 8, 2)->nullable();
            $table->string('fuente', 20)->default('GPS');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');

            $table->index('visita_id');
        });

        // punto_geografico es NOT NULL aquí (a diferencia de ubicaciones.punto_geografico):
        // toda captura GPS de campo debe traer coordenadas completas.
        DB::statement('ALTER TABLE ubicaciones_visitas ADD COLUMN punto_geografico geography(Point,4326) NOT NULL');
        DB::statement("ALTER TABLE ubicaciones_visitas ADD CONSTRAINT chk_ubicaciones_visitas_fuente CHECK (fuente IN ('GPS','RED','MANUAL'))");
        DB::statement('CREATE INDEX idx_ubicaciones_visitas_punto_geografico ON ubicaciones_visitas USING GIST (punto_geografico)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_visitas');
    }
};

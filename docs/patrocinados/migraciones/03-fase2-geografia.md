# Fase 2 — Geografía

## 2.1 `departamento`

`2026_09_01_000010_create_departamento_table.php`

```php
Schema::create('departamento', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('codigo', 30)->unique()->nullable();
    $table->string('departamento', 150);
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
});
```

## 2.2 `municipios`

`2026_09_01_000011_create_municipios_table.php`

```php
Schema::create('municipios', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('departamento_id')->constrained('departamento')->onDelete('restrict');
    $table->string('codigo', 30)->unique()->nullable();
    $table->string('municipio', 150);
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->index('departamento_id');
});
```

## 2.3 `comunidades`

`2026_09_01_000012_create_comunidades_table.php`

> El docx **no** marca `codigo` como `UNIQUE` en esta tabla (a diferencia de `departamento`/`municipios`) — se respeta tal cual, no se agrega unicidad por analogía.

```php
Schema::create('comunidades', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('municipio_id')->constrained('municipios')->onDelete('restrict');
    $table->string('codigo', 30)->nullable();
    $table->string('comunidad', 180);
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->index('municipio_id');
});
```

## 2.4 `ubicaciones`

`2026_09_01_000013_create_ubicaciones_table.php`

Requiere que la Fase 0 (PostGIS) ya haya corrido. Enum sugerido para `tipo` (a confirmar con negocio, ver `docs/patrocinados/03-geografia.md`): `DOMICILIO`, `ESCUELA`, `PUNTO_REFERENCIA`, `OTRO`.

```php
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
        Schema::create('ubicaciones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('comunidad_id')->constrained('comunidades')->onDelete('restrict');
            $table->string('nombre', 180);
            $table->string('tipo', 50)->nullable();
            $table->text('direccion')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('precision_metros', 8, 2)->nullable();
            // punto_geografico se agrega abajo vía SQL crudo — Blueprint no tiene tipo GEOGRAPHY nativo.
            $table->boolean('estado')->default(true);
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

            $table->index('comunidad_id');
        });

        DB::statement('ALTER TABLE ubicaciones ADD COLUMN punto_geografico geography(Point,4326) NULL');

        DB::statement("ALTER TABLE ubicaciones ADD CONSTRAINT chk_ubicaciones_tipo CHECK (tipo IS NULL OR tipo IN ('DOMICILIO','ESCUELA','PUNTO_REFERENCIA','OTRO'))");

        // Índice espacial — necesario para cualquier query ST_DWithin/ST_Distance eficiente.
        DB::statement('CREATE INDEX idx_ubicaciones_punto_geografico ON ubicaciones USING GIST (punto_geografico)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones');
    }
};
```

**Regla de derivación (Repository, no migración)**: `EloquentUbicacionRepository::create()`/`update()` calculan `punto_geografico` con `ST_MakePoint(longitude, latitude)::geography` cada vez que cambian `latitude`/`longitude` — ejemplo de la sentencia usada dentro del Repository:

```php
DB::connection('pgsql_patrocinados')->statement(
    'UPDATE ubicaciones SET punto_geografico = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
    [$longitude, $latitude, $id]
);
```

**Cuidado con el orden**: PostGIS espera `ST_MakePoint(longitud, latitud)` — X antes que Y. Invertirlo es el error más común al integrar PostGIS y no lanza ningún error, solo ubica todo en el hemisferio equivocado.

## Verificación de la fase

- [ ] `SELECT ST_AsText(punto_geografico) FROM ubicaciones LIMIT 1;` tras un insert de prueba muestra `POINT(-66.1569 -17.3938)` (longitud primero).
- [ ] `\d ubicaciones` muestra el índice `idx_ubicaciones_punto_geografico` de tipo `gist`.
- [ ] Borrar una `comunidad` con `ubicaciones` asociadas falla por `restrict` (no hay cascade en el docx).

# Fase 4 — Patrocinados

## 4.1 `estados_patrocinados`

`2026_09_01_000030_create_estados_patrocinados_table.php`

Enum cerrado (ver `docs/patrocinados/05-patrocinados.md`, decisión 2 — confirmar con negocio antes de migrar): `ACTIVO`, `NO_ENCONTRADO`, `INACTIVO_NO_UBICADO`, `MAYOR_DE_EDAD`.

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
        Schema::create('estados_patrocinados', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('estado', 20)->default('ACTIVO');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
        });

        DB::statement("ALTER TABLE estados_patrocinados ADD CONSTRAINT chk_estados_patrocinados_estado CHECK (estado IN ('ACTIVO','NO_ENCONTRADO','INACTIVO_NO_UBICADO','MAYOR_DE_EDAD'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_patrocinados');
    }
};
```

Seeder inmediato (no esperar a producción, se usa desde el primer test): 4 filas, una por valor del enum.

## 4.2 `tipos_parentescos`

`2026_09_01_000031_create_tipos_parentescos_table.php`

```php
Schema::create('tipos_parentescos', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('parentesco', 120);
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
});
```

## 4.3 `patrocinados`

`2026_09_01_000032_create_patrocinados_table.php`

```php
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
```

## 4.4 `tutores`

`2026_09_01_000033_create_tutores_table.php`

> **Ajuste respecto del docx** (decisión de negocio ya cerrada, ver `docs/patrocinados/05-patrocinados.md` decisión 1): el campo `Parentesco VARCHAR(100)` del documento se reemplaza por `tipo_parentesco_id UUID NOT NULL` con FK a `tipos_parentescos`. No se crea la columna VARCHAR libre.

```php
Schema::create('tutores', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('cascade');
    $table->string('nombres', 120);
    $table->string('apellidos', 160);
    $table->foreignUuid('tipo_parentesco_id')->constrained('tipos_parentescos')->onDelete('restrict'); // reemplaza a Parentesco VARCHAR del docx
    $table->string('telefono', 40)->nullable();
    $table->string('direccion', 160);
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->timestamp('deleted_at')->nullable();
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->index('patrocinado_id');
});
```

`onDelete('cascade')` en `patrocinado_id` es intencional aquí (a diferencia de la regla `restrict` general): si un patrocinado se elimina definitivamente (caso raro, ya que la tabla usa soft delete), sus tutores no tienen razón de existir de forma independiente. En la práctica, como `patrocinados` usa `SoftDeletes`, este `cascade` casi nunca se dispara — es una salvaguarda para el caso de un `forceDelete()` administrativo.

## 4.5 `historial_ubicaciones`

`2026_09_01_000034_create_historial_ubicaciones_table.php`

```php
Schema::create('historial_ubicaciones', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('cascade');
    $table->foreignUuid('comunidad_id')->constrained('comunidades')->onDelete('restrict');
    $table->foreignUuid('ubicacion_id')->nullable()->constrained('ubicaciones')->onDelete('restrict');
    $table->date('fecha_inicio');
    $table->date('fecha_fin')->nullable();
    $table->foreignUuid('created_by')->nullable()->constrained('usuarios')->onDelete('set null');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->index('patrocinado_id');
});

// Regla de sincronía (Application/Patrocinados/Handlers/CambiarUbicacionPatrocinadoHandler.php):
// solo una fila con fecha_fin IS NULL por patrocinado_id. Se refuerza con índice único parcial:
DB::statement('CREATE UNIQUE INDEX uq_historial_ubicaciones_activa ON historial_ubicaciones (patrocinado_id) WHERE fecha_fin IS NULL');
```

Este índice único parcial es la aplicación directa, a nivel de BD, del hallazgo §8.1 del plan de revisión (patrón "estado actual + historial" sin garantía de una sola fila activa) — sin él, un bug en el Handler podría dejar dos filas abiertas simultáneamente para el mismo patrocinado sin que la BD lo impida.

## Verificación de la fase

- [ ] Insertar dos filas de `historial_ubicaciones` con `fecha_fin IS NULL` para el mismo `patrocinado_id` falla por el índice único parcial — probarlo con SQL directo, no solo desde el Handler, para confirmar que la barrera es real y no solo aplicativa.
- [ ] `tutores.tipo_parentesco_id` es `NOT NULL` y falla si se omite.
- [ ] `patrocinados.estado_id` no acepta un UUID que no exista en `estados_patrocinados` (FK).

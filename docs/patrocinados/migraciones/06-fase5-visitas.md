# Fase 5 — Visitas (10 tablas)

> Bloqueante antes de migrar: cerrar con negocio la regla completa de reprogramación (ver `docs/patrocinados/06-visitas.md`, decisión 1). El DDL de `visitas` de abajo ya incluye el campo `intentos_reprogramacion` que esa regla necesita, pero el *comportamiento* (cuántos intentos, quién decide la baja) se implementa en el Handler, no aquí.

## 5.1 `motivos_visitas`

`2026_09_01_000040_create_motivos_visitas_table.php`

```php
Schema::create('motivos_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('motivo_visita', 120)->unique();
    $table->string('descripcion', 255)->nullable();
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
});
```

Seed inicial documentado en el docx: `Actualizacion de perfil` / *"Llenado de cuestionario"*.

## 5.2 `categorias_observaciones`

`2026_09_01_000041_create_categorias_observaciones_table.php`

```php
Schema::create('categorias_observaciones', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('codigo', 50)->unique();
    $table->string('categoria_observaciones', 120)->unique();
    $table->string('descripcion', 255)->nullable();
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');
});
```

Seed inicial documentado en el docx: `codigo='EDU'`, `categoria='Educacion'`, *"Observaciones relacionadas con asistencia y situación educativa"*.

## 5.3 `planes_visitas`

`2026_09_01_000042_create_planes_visitas_table.php`

Enum cerrado para `estado`: `ACTIVO`, `CERRADO`, `CANCELADO` (confirmar con negocio; el docx solo documenta el default `ACTIVO`).

```php
Schema::create('planes_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('plan', 180);
    $table->smallInteger('anio');
    $table->date('fecha_inicio');
    $table->date('fecha_fin');
    $table->string('estado', 30)->default('ACTIVO');
    $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');
    $table->foreignUuid('updated_by')->nullable()->constrained('usuarios')->onDelete('set null');

    $table->index('anio');
});

DB::statement("ALTER TABLE planes_visitas ADD CONSTRAINT chk_planes_visitas_estado CHECK (estado IN ('ACTIVO','CERRADO','CANCELADO'))");
```

## 5.4 `visitas`

`2026_09_01_000043_create_visitas_table.php`

Enums cerrados (ver `docs/patrocinados/06-visitas.md` decisión 2):

```text
estado:                PLANIFICADA, EN_CURSO, FINALIZADA, NO_ENCONTRADO, REPROGRAMADA, CANCELADA
estado_revision:       PENDIENTE, APROBADA, RECHAZADA, REQUIERE_CORRECCION
estado_sincronizacion: PENDIENTE, SINCRONIZADO, ERROR
```

> **Ajuste respecto del docx**: se agrega `intentos_reprogramacion SMALLINT DEFAULT 0`, requerido por la regla de negocio de reprogramación (bloqueante, ver arriba) y no presente en el documento original. Sin esta columna, `ReprogramarVisitaHandler` no tiene dónde contar los intentos.

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
        Schema::create('visitas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('plan_visita_id')->nullable()->constrained('planes_visitas')->onDelete('set null');
            $table->foreignUuid('patrocinado_id')->constrained('patrocinados')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict'); // técnico asignado actual, ver regla de sincronía con asignaciones_visitas
            $table->foreignUuid('motivo_visita_id')->nullable()->constrained('motivos_visitas')->onDelete('set null');
            $table->date('fecha_programada')->nullable();
            $table->timestampTz('fecha_habilitacion')->nullable();
            $table->timestampTz('fecha_inicio')->nullable();
            $table->timestampTz('fecha_finalizacion')->nullable();
            $table->string('estado', 30)->default('PLANIFICADA');
            $table->string('estado_revision', 30)->default('PENDIENTE'); // ver regla de sincronía con revisiones_visitas
            $table->string('estado_sincronizacion', 30)->default('PENDIENTE');
            $table->integer('version')->default(1); // concurrencia optimista para sync offline
            $table->smallInteger('intentos_reprogramacion')->default(0); // ajuste respecto del docx, ver nota arriba
            $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
            // Sin updated_by: ver 00-indice.md, es intencional (created_by + user_id ya cubren autoría).

            $table->index('patrocinado_id');
            $table->index(['patrocinado_id', 'estado']);
            $table->index('user_id');
            $table->index('plan_visita_id');
        });

        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado CHECK (estado IN ('PLANIFICADA','EN_CURSO','FINALIZADA','NO_ENCONTRADO','REPROGRAMADA','CANCELADA'))");
        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado_revision CHECK (estado_revision IN ('PENDIENTE','APROBADA','RECHAZADA','REQUIERE_CORRECCION'))");
        DB::statement("ALTER TABLE visitas ADD CONSTRAINT chk_visitas_estado_sincronizacion CHECK (estado_sincronizacion IN ('PENDIENTE','SINCRONIZADO','ERROR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas');
    }
};
```

## 5.5 `asignaciones_visitas`

`2026_09_01_000044_create_asignaciones_visitas_table.php`

```php
Schema::create('asignaciones_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
    $table->foreignUuid('tecnico_id')->constrained('usuarios')->onDelete('restrict');
    $table->foreignUuid('assigned_by')->constrained('usuarios')->onDelete('restrict');
    $table->timestampTz('fecha_asignacion');
    $table->timestampTz('fecha_desasignacion')->nullable();
    $table->boolean('estado')->default(true);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');

    $table->index('visita_id');
});

// Refuerza la regla de sincronía visitas.user_id <-> asignaciones_visitas (hallazgo §8.1 / §8.2 del plan de revisión):
// como mucho una asignación activa por visita.
DB::statement('CREATE UNIQUE INDEX uq_asignaciones_visitas_activa ON asignaciones_visitas (visita_id) WHERE estado = TRUE');
```

`onDelete('cascade')` en `visita_id`: si una visita se elimina definitivamente, su historial de asignaciones no tiene sentido de forma independiente (a diferencia de la relación con `usuarios`, donde `restrict` protege de perder trazabilidad de qué técnico hizo qué).

## 5.6 `habilitaciones_visitas`

`2026_09_01_000045_create_habilitaciones_visitas_table.php`

Enum cerrado: `ACTIVA`, `EXPIRADA`, `REVOCADA`.

```php
Schema::create('habilitaciones_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
    $table->foreignUuid('tecnico_id')->constrained('usuarios')->onDelete('restrict');
    $table->foreignUuid('dispositivo_id')->constrained('dispositivos')->onDelete('restrict');
    $table->foreignUuid('authorized_by')->constrained('usuarios')->onDelete('restrict');
    $table->timestampTz('fecha_habilitacion');
    $table->timestampTz('fecha_expiracion');
    $table->string('estado', 20)->default('ACTIVA');
    $table->timestampTz('fecha_revocacion')->nullable();
    $table->foreignUuid('revoked_by')->nullable()->constrained('usuarios')->onDelete('set null');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');

    $table->index(['visita_id', 'dispositivo_id']);
});

DB::statement("ALTER TABLE habilitaciones_visitas ADD CONSTRAINT chk_habilitaciones_visitas_estado CHECK (estado IN ('ACTIVA','EXPIRADA','REVOCADA'))");

// Como mucho una habilitación activa por (visita, dispositivo):
DB::statement("CREATE UNIQUE INDEX uq_habilitaciones_visitas_activa ON habilitaciones_visitas (visita_id, dispositivo_id) WHERE estado = 'ACTIVA'");
```

## 5.7 `ubicaciones_visitas`

`2026_09_01_000046_create_ubicaciones_visitas_table.php`

Enum cerrado para `fuente`: `GPS`, `RED`, `MANUAL`.

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

        DB::statement('ALTER TABLE ubicaciones_visitas ADD COLUMN punto_geografico geography(Point,4326) NOT NULL');
        DB::statement("ALTER TABLE ubicaciones_visitas ADD CONSTRAINT chk_ubicaciones_visitas_fuente CHECK (fuente IN ('GPS','RED','MANUAL'))");
        DB::statement('CREATE INDEX idx_ubicaciones_visitas_punto_geografico ON ubicaciones_visitas USING GIST (punto_geografico)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ubicaciones_visitas');
    }
};
```

`punto_geografico` es `NOT NULL` aquí (a diferencia de `ubicaciones.punto_geografico`, que es nullable) — el docx así lo especifica, porque toda captura GPS de campo debe traer coordenadas completas; una `Ubicacion` de catálogo puede crearse sin coordenadas aún.

## 5.8 `observaciones_visitas`

`2026_09_01_000047_create_observaciones_visitas_table.php`

Enum sugerido para `tipo` (el docx solo documenta el default `GENERAL`; confirmar lista completa con negocio): `GENERAL`, `EDUCATIVA`, `SALUD`, `FAMILIAR`.

```php
Schema::create('observaciones_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
    $table->foreignUuid('categoria_id')->nullable()->constrained('categorias_observaciones')->onDelete('set null');
    $table->string('tipo', 30)->default('GENERAL');
    $table->text('observacion');
    $table->foreignUuid('created_by')->constrained('usuarios')->onDelete('restrict');
    $table->timestamp('created_at');
    $table->timestamp('updated_at');

    $table->index('visita_id');
});

DB::statement("ALTER TABLE observaciones_visitas ADD CONSTRAINT chk_observaciones_visitas_tipo CHECK (tipo IN ('GENERAL','EDUCATIVA','SALUD','FAMILIAR'))");
```

## 5.9 `fotos_visitas`

`2026_09_01_000048_create_fotos_visitas_table.php`

```php
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
```

`fotos_visitas` no tiene `punto_geografico` PostGIS en el docx (solo lat/lng planos) — se respeta tal cual, no se agrega por analogía con las otras dos tablas de ubicación; no hay caso de uso de búsqueda espacial documentado sobre fotos.

## 5.10 `revisiones_visitas`

`2026_09_01_000049_create_revisiones_visitas_table.php`

El docx no cierra el enum de `estado` aquí (solo dice `VARCHAR(20) NOT NULL` sin default) — usar el mismo dominio que `visitas.estado_revision` para que la regla de sincronía (Handler `RevisarVisitaHandler`) tenga sentido: `APROBADA`, `RECHAZADA`, `REQUIERE_CORRECCION`.

```php
Schema::create('revisiones_visitas', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('visita_id')->constrained('visitas')->onDelete('cascade');
    $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
    $table->timestampTz('fecha_revision');
    $table->string('estado', 20);
    $table->text('comentarios')->nullable();
    $table->boolean('requiere_correccion')->default(false);
    $table->timestamp('created_at');
    $table->timestamp('updated_at');

    $table->index('visita_id');
});

DB::statement("ALTER TABLE revisiones_visitas ADD CONSTRAINT chk_revisiones_visitas_estado CHECK (estado IN ('APROBADA','RECHAZADA','REQUIERE_CORRECCION'))");
```

## Verificación de la fase

- [ ] Insertar una segunda `asignacion_visita` activa para la misma `visita_id` falla por `uq_asignaciones_visitas_activa`.
- [ ] Insertar una segunda `habilitacion_visita` con `estado='ACTIVA'` para el mismo par `(visita_id, dispositivo_id)` falla por `uq_habilitaciones_visitas_activa`.
- [ ] `ubicaciones_visitas.punto_geografico` rechaza `NULL` (a diferencia de `ubicaciones.punto_geografico`).
- [ ] Los 6 `CHECK` de enums de esta fase rechazan valores fuera de lista al probarlos con SQL directo.
- [ ] Borrar una `visita` (soft delete a nivel Eloquent) no dispara los `ON DELETE CASCADE` físicos de `asignaciones_visitas`/`habilitaciones_visitas`/etc. — **importante**: el `cascade` de estas FK es para el borrado *físico* (`forceDelete`), el soft delete de Eloquent no lo activa nunca porque no ejecuta un `DELETE` real. Confirmar que nadie llama `forceDelete()` sobre `Visita` sin entender que arrastra todo su árbol de evidencia.

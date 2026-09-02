# Fase 6 — Sincronización

## 6.1 `lotes_sincronizacion`

`2026_09_01_000050_create_lotes_sincronizacion_table.php`

Enum cerrado: `SINCRONIZANDO`, `COMPLETADO`, `ERROR`.

> **Ajuste respecto del docx** (ver `00-indice.md`): se agrega `updated_at TIMESTAMPTZ NOT NULL`, ausente en el documento original, para poder detectar/depurar lotes que quedan colgados en `SINCRONIZANDO` sin `fecha_fin`.

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
        Schema::create('lotes_sincronizacion', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('dispositivo_id')->constrained('dispositivos')->onDelete('restrict');
            $table->foreignUuid('user_id')->constrained('usuarios')->onDelete('restrict');
            $table->timestampTz('fecha_inicio');
            $table->timestampTz('fecha_fin')->nullable();
            $table->integer('registros_enviados')->default(0);
            $table->integer('registros_recibidos')->default(0);
            $table->string('estado', 20)->default('SINCRONIZANDO');
            $table->text('mensaje_error')->nullable();
            $table->timestampTz('updated_at'); // ajuste respecto del docx, ver nota arriba

            $table->index('dispositivo_id');
            $table->index('estado');
        });

        DB::statement("ALTER TABLE lotes_sincronizacion ADD CONSTRAINT chk_lotes_sincronizacion_estado CHECK (estado IN ('SINCRONIZANDO','COMPLETADO','ERROR'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes_sincronizacion');
    }
};
```

## 6.2 `elementos_sincronizacion`

`2026_09_01_000051_create_elementos_sincronizacion_table.php`

Enums cerrados:
```text
estado:    PENDIENTE, SINCRONIZADO, ERROR
operacion: CREATE, UPDATE, DELETE
```

```php
Schema::create('elementos_sincronizacion', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('lote_sincronizacion_id')->constrained('lotes_sincronizacion')->onDelete('cascade');
    $table->string('tipo_entidad', 100);
    $table->uuid('entidad_id');
    $table->string('operacion', 20);
    $table->char('hash_datos', 64)->nullable();
    $table->string('estado', 20)->default('PENDIENTE');
    $table->smallInteger('intentos')->default(0);
    $table->text('mensaje_error')->nullable();
    $table->timestampTz('fecha_sincronizacion')->nullable();
    $table->timestamp('created_at');
    $table->timestamp('updated_at');

    $table->index('lote_sincronizacion_id');
    $table->index(['tipo_entidad', 'entidad_id']);
    // Soporta la idempotencia de reenvío exigida en docs/patrocinados/07-sincronizacion.md:
    $table->unique(['tipo_entidad', 'entidad_id', 'hash_datos'], 'uq_elementos_sync_entidad_hash');
});

DB::statement("ALTER TABLE elementos_sincronizacion ADD CONSTRAINT chk_elementos_sincronizacion_estado CHECK (estado IN ('PENDIENTE','SINCRONIZADO','ERROR'))");
DB::statement("ALTER TABLE elementos_sincronizacion ADD CONSTRAINT chk_elementos_sincronizacion_operacion CHECK (operacion IN ('CREATE','UPDATE','DELETE'))");
```

`entidad_id` es un `UUID` "suelto" sin FK real (el docx lo especifica así, con razón: apunta a filas de tablas distintas según `tipo_entidad` — `visitas`, `patrocinados`, etc. — una FK polimórfica no es expresable como constraint nativo de Postgres sin triggers adicionales). La integridad de esa referencia queda a cargo del `SincronizacionRouterService` de la capa de aplicación, no de la BD.

**Advertencia sobre la unicidad de idempotencia**: `hash_datos` es `NULLABLE` en el docx, y en Postgres un índice `UNIQUE` **no** considera iguales dos valores `NULL` — es decir, `uq_elementos_sync_entidad_hash` no bloquea el reenvío duplicado de un elemento que llegue con `hash_datos = NULL`. Si la app Flutter puede enviar elementos sin hash, la idempotencia real debe reforzarse en `ProcesarElementoSincronizacionHandler` (comprobar existencia por `entidad_id` + `operacion` antes de procesar), no confiar solo en este índice. Si se puede garantizar que `hash_datos` siempre viene poblado desde el cliente, considerar además hacerlo `NOT NULL` aquí.

## Verificación de la fase

- [ ] Reenviar el mismo `(tipo_entidad, entidad_id, hash_datos)` no crea una segunda fila — el `INSERT` falla por `uq_elementos_sync_entidad_hash` y el Handler lo interpreta como "ya sincronizado", no como error.
- [ ] `operacion` fuera de `CREATE`/`UPDATE`/`DELETE` falla por el `CHECK`.
- [ ] Borrar un `lote_sincronizacion` arrastra en cascada sus `elementos_sincronizacion` (intencional — el elemento no tiene sentido sin su lote).

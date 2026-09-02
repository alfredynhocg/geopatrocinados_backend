# Implementación de la BD — Módulo Patrocinados / Visitas

> Esto es el detalle de **schema/migraciones real** (no arquitectura de aplicación). Complementa a `docs/patrocinados/01-...09-...md` (que cubren Domain/Application/Infrastructure/Http) con el DDL exacto de las 25 tablas, listo para convertir en archivos de migración de Laravel.
> Motor: PostgreSQL + PostGIS, conexión `pgsql_patrocinados` (ver `docs/patrocinados/01-infraestructura-base.md`).
> Todas las migraciones van en `database/migrations/patrocinados/` y declaran `protected $connection = 'pgsql_patrocinados';` en la clase — así no dependen del flag `--database=` al correr `php artisan migrate`.

## Convención usada en estos documentos

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
        Schema::create('nombre_tabla', function (Blueprint $table) {
            // ...
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nombre_tabla');
    }
};
```

- PK: `$table->uuid('id')->primary();` — el valor se genera en PHP vía `HasUuids` del modelo, la migración solo define la columna.
- FK: siempre `->constrained('tabla')->onDelete('restrict')` salvo que se indique lo contrario — el docx no define ON DELETE CASCADE en ningún caso, así que por defecto se usa `restrict` (evita borrados en cascada no solicitados). Se señala explícitamente cualquier excepción.
- `CHECK` constraints de enums: se agregan con `DB::statement()` al final de la migración de la tabla (Laravel no tiene un builder nativo para `CHECK` que cubra todos los casos de forma portable) — no se dejan solo como validación de Laravel, porque la app Flutter también sincroniza datos directo vía la API de sync (Etapa 7), y el `CHECK` es la última barrera de integridad.
- Índices únicos parciales (`WHERE estado = ...`): también vía `DB::statement()`, Laravel no soporta índices parciales en el Schema Builder.

## Fases (mismo orden que la sección 4 del plan de revisión y la hoja de ruta)

| Archivo | Fase | Tablas |
|---|---|---|
| [01-fase0-extension-postgis.md](01-fase0-extension-postgis.md) | 0 | Extensión `postgis` |
| [02-fase1-acceso.md](02-fase1-acceso.md) | 1 | `roles`, `permisos`, `usuarios`, `usuarios_roles`, `roles_permisos` |
| [03-fase2-geografia.md](03-fase2-geografia.md) | 2 | `departamento`, `municipios`, `comunidades`, `ubicaciones` |
| [04-fase3-dispositivos.md](04-fase3-dispositivos.md) | 3 | `dispositivos` |
| [05-fase4-patrocinados.md](05-fase4-patrocinados.md) | 4 | `estados_patrocinados`, `tipos_parentescos`, `patrocinados`, `tutores`, `historial_ubicaciones` |
| [06-fase5-visitas.md](06-fase5-visitas.md) | 5 | `motivos_visitas`, `categorias_observaciones`, `planes_visitas`, `visitas`, `asignaciones_visitas`, `habilitaciones_visitas`, `ubicaciones_visitas`, `observaciones_visitas`, `fotos_visitas`, `revisiones_visitas` |
| [07-fase6-sincronizacion.md](07-fase6-sincronizacion.md) | 6 | `lotes_sincronizacion`, `elementos_sincronizacion` |
| [08-fase7-auditoria.md](08-fase7-auditoria.md) | 7 | `registros_auditoria` |

## Comando de migración

```bash
php artisan migrate --path=database/migrations/patrocinados
```

(No hace falta `--database=` porque cada clase de migración fija su propia conexión.)

## Inconsistencias detectadas en el documento fuente al pasarlo a DDL (decidir antes de migrar)

Al bajar cada tabla del docx a columnas reales aparecieron 3 huecos que el documento original no señala. Se resuelven aquí con una recomendación explícita — no se dejan implícitos como en el docx:

1. **`dispositivos` no tiene `created_at`/`updated_at`** (solo `fecha_registro`), pero sí tiene `updated_by`. No tiene sentido guardar "quién lo modificó por última vez" sin saber "cuándo". **Recomendación: agregar `updated_at TIMESTAMPTZ NOT NULL`** a la tabla (ver fase 3). No se agrega `created_at` porque `fecha_registro` ya cumple ese rol semánticamente.
2. **`lotes_sincronizacion` no tiene `created_at`/`updated_at`** en absoluto (solo `fecha_inicio`/`fecha_fin`). Es aceptable para una tabla de eventos de sync de vida corta, pero **se recomienda agregar `updated_at TIMESTAMPTZ NOT NULL`** para poder ordenar/depurar lotes que quedan colgados en `SINCRONIZANDO` (ver fase 6).
3. **`visitas`, `asignaciones_visitas`, `habilitaciones_visitas`, `ubicaciones_visitas`, `observaciones_visitas`, `revisiones_visitas` no tienen `updated_by`** (a diferencia de casi todas las demás tablas del módulo). Se deja tal cual el docx — son tablas mayormente append-only donde "quién hizo la acción" ya queda registrado en columnas explícitas propias (`created_by`, `assigned_by`, `authorized_by`, `revoked_by`, `user_id` según la tabla), así que un `updated_by` genérico sería redundante. No se agrega.

Todas las decisiones de enums cerrados, FKs agregadas (ej. `tutores.tipo_parentesco_id`) e índices únicos parciales que ya se resolvieron en el plan de revisión y en la hoja de ruta se aplican tal cual en el DDL de cada fase — no se repiten aquí, se remite a `docs/patrocinados/05-patrocinados.md` y `docs/patrocinados/06-visitas.md` para el razonamiento de negocio detrás de cada una.

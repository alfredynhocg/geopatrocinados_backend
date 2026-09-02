# Hoja de ruta de implementación — Módulo Patrocinados / Visitas

> Índice general. Cada etapa tiene su propio archivo con detalle de tareas, migraciones, estructura DDD, endpoints y criterios de aceptación.
> Precondición de todo lo que sigue: **[PLAN_INTEGRACION_PATROCINADOS.md](../../PLAN_INTEGRACION_PATROCINADOS.md)** (revisión del docx original, decisiones de arquitectura ya tomadas, y evaluación del diseño relacional).

## Decisiones ya cerradas (no reabrir sin motivo)

1. Conexión propia `pgsql_patrocinados` (PostgreSQL + PostGIS), separada de la conexión `mysql` default de mentabit.
2. Módulo con auth/roles/permisos **propios e independientes** de `t_usuario` — no se toca el sistema legado.
3. Regla de sincronía para el patrón "estado actual + historial" (ver hallazgo §8 del plan de revisión): **el Handler transaccional actualiza ambos lados en la misma transacción** — no triggers de BD, no subqueries calculadas en cada lectura. Se aplica en las 4 parejas: `patrocinados`↔`historial_ubicaciones`, `visitas.user_id`↔`asignaciones_visitas`, `visitas.estado_revision`↔`revisiones_visitas`, `dispositivos.Estado`↔`habilitaciones_visitas`.
4. Prefijo de rutas API: `/api/v1/patrocinados/...` en archivo de rutas propio `routes/api/patrocinados.php`. La entidad "niño patrocinado" (tabla `patrocinados`) se expone como `/api/v1/patrocinados/ninos` para evitar el choque de nombre módulo=recurso (`/patrocinados/patrocinados`).
5. UUID generado en PHP (`HasUuids` de Laravel), no por la BD — mantiene paridad con SQLite en tests unitarios.

## Orden de etapas y dependencias

```text
Etapa 1 — Infraestructura base            (sin dependencias)
   │
   ▼
Etapa 2 — AccesoPatrocinados              (usuarios/roles/permisos — desbloquea todo lo demás)
   │
   ├──▶ Etapa 3 — Geografía               (departamento→municipios→comunidades→ubicaciones)
   │        │
   │        ▼
   ├──▶ Etapa 4 — Dispositivos            (depende de usuarios)
   │        │
   │        ▼
   ├──▶ Etapa 5 — Patrocinados            (depende de Geografía)
   │        │
   │        ▼
   └──▶ Etapa 6 — Visitas                 (depende de Patrocinados, Dispositivos, Geografía)
            │
            ▼
        Etapa 7 — Sincronización          (depende de Dispositivos, y de todo lo que se sincroniza)
            │
            ▼
        Etapa 8 — Auditoría transversal   (se conecta a las escrituras de TODAS las etapas anteriores)
            │
            ▼
        Etapa 9 — Testing y QA integral + Hardening de despliegue
```

Las etapas 3 y 4 no dependen entre sí y pueden implementarse en paralelo por dos desarrolladores distintos una vez cerrada la Etapa 2. La Etapa 8 (auditoría) conviene implementarla como servicio genérico apenas termine la Etapa 2 (para no tener que retrofitear los Handlers de las etapas 3-7 después), aunque su tabla y su primer caso de uso real se documentan al final por claridad de dependencias de datos.

## Archivos de esta carpeta

| Archivo | Etapa | Contenido |
|---|---|---|
| [01-infraestructura-base.md](01-infraestructura-base.md) | 1 | Conexión BD, extensión PostGIS, esqueleto de carpetas, `.env`, comando de migración |
| [02-acceso-patrocinados.md](02-acceso-patrocinados.md) | 2 | `usuarios`, `roles`, `permisos`, `usuarios_roles`, `roles_permisos` + guard/middleware de permisos propio |
| [03-geografia.md](03-geografia.md) | 3 | `departamento`, `municipios`, `comunidades`, `ubicaciones` (+ manejo de `GEOGRAPHY(POINT)`) |
| [04-dispositivos.md](04-dispositivos.md) | 4 | `dispositivos` — alta, habilitación, revocación |
| [05-patrocinados.md](05-patrocinados.md) | 5 | `estados_patrocinados`, `patrocinados`, `tipos_parentescos`, `tutores`, `historial_ubicaciones` |
| [06-visitas.md](06-visitas.md) | 6 | Las 10 tablas del núcleo operativo: planificación, asignación, habilitación, evidencia y revisión de visitas |
| [07-sincronizacion.md](07-sincronizacion.md) | 7 | `lotes_sincronizacion`, `elementos_sincronizacion` — contrato API de sync offline |
| [08-auditoria-transversal.md](08-auditoria-transversal.md) | 8 | `registros_auditoria` + `AuditoriaService` reutilizado por todos los módulos |
| [09-testing-y-qa.md](09-testing-y-qa.md) | 9 | Estrategia de test (Postgres real en CI), checklist de hardening, despliegue |

## Implementación de la base de datos (schema/migraciones real)

Los 9 archivos de arriba cubren la arquitectura de aplicación (Domain/Application/Infrastructure/Http). El **DDL exacto** de las 25 tablas — código de migración de Laravel listo para copiar, columna por columna, con `CHECK constraints`, índices únicos parciales e índices `GIST` — está en **[migraciones/00-indice.md](migraciones/00-indice.md)**, organizado por las mismas 8 fases (0 a 7) del plan de revisión.

## Definition of Done global del módulo

- Las 25 tablas migradas en `pgsql_patrocinados` en el orden de dependencias documentado.
- Cada tabla tiene su Modelo Eloquent con `$connection = 'pgsql_patrocinados'` explícito.
- Cada entidad de negocio (no las tablas pivote puras) tiene Contract + Exception (Domain), DTO + Commands/Queries + Handlers (Application), Repository Eloquent (Infrastructure), Controller + FormRequests (Http) — sin excepciones al patrón de `CLAUDE.md`.
- Ningún Handler de escritura sale de una transacción sin haber llamado al `AuditoriaService`.
- Los 4 pares "estado actual + historial" tienen su regla de sincronía implementada y cubierta por un test que verifica ambos lados tras la operación.
- Suite de Feature Tests corre contra Postgres real (no SQLite) en CI.
- `CLAUDE.md` actualizado con la sección del nuevo módulo (siguiendo el mismo formato que la sección "Módulo de Pagos — Referencia completa").

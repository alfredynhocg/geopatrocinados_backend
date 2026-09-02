# Plan de Integración — Módulo Patrocinados / Visitas

> Fuente: `estructura_base_datos_sistema_patrocinados.docx`
> Estado: **planificación — sin implementar**
> Decisiones ya tomadas con el usuario (2026-08-31):
> 1. El módulo usa una **conexión PostgreSQL + PostGIS separada** (segunda conexión de BD dentro del mismo Laravel), no se adapta a MySQL.
> 2. El módulo es **independiente**: tiene su propia tabla `usuarios`/`roles`/`permisos`, no reutiliza `t_usuario` ni el sistema de permisos legado de mentabit.
>
> **Planificación de implementación por etapas:** ver [docs/patrocinados/00-hoja-de-ruta.md](docs/patrocinados/00-hoja-de-ruta.md) — 9 etapas, una por archivo, con estructura DDD, endpoints y criterios de aceptación de cada una.
> **Implementación de la BD (DDL real):** ver [docs/patrocinados/migraciones/00-indice.md](docs/patrocinados/migraciones/00-indice.md) — migraciones de Laravel completas por fase (0 a 7), columna por columna, con `CHECK constraints`, índices únicos parciales, PostGIS y las 3 correcciones de schema detectadas al bajar el docx a DDL real.

---

## 1. Qué es esto y por qué es distinto al resto del proyecto

El documento describe un **sistema de gestión y seguimiento de visitas a niños patrocinados** (ONG de apadrinamiento), con app móvil Flutter offline-first + backend Laravel. No tiene relación funcional con cursos/diplomados/pagos de mentabit — es un **bounded context nuevo** que convive en el mismo repo/API.

Diferencias clave frente al resto del proyecto (documentadas para que no se traten como error):

| Aspecto | Resto del proyecto (mentabit / cenefco) | Módulo Patrocinados |
|---|---|---|
| Motor de BD | MySQL 8 | **PostgreSQL + PostGIS** (conexión propia) |
| PK | `bigIncrements` (tablas nuevas) / compuesta (legado) | **UUID** en todas las tablas |
| Timestamps | `timestampTz` sin softDeletes salvo tablas `web_*` | `TIMESTAMPTZ` + `SoftDeletes` en entidades con trazabilidad legal (usuarios, patrocinados, tutores, visitas, fotos) |
| Geolocalización | No aplica | `GEOGRAPHY(POINT,4326)` vía PostGIS + lat/lng planos redundantes |
| Auditoría | Sin tabla dedicada | Tabla `registros_auditoria` (JSONB before/after) |
| Auth/roles | Sanctum + `t_usuario` + middleware `permiso:recurso.accion` | Tablas propias `usuarios`/`roles`/`permisos` — **auth independiente**, probablemente con su propio guard Sanctum o token propio |
| Offline sync | No aplica | `lotes_sincronizacion` + `elementos_sincronizacion` (app Flutter con SQLite local) |

**Implicación de arquitectura:** este módulo sigue el mismo patrón DDD+CQRS de `CLAUDE.md` (Domain/Application/Infrastructure/Http), pero cada Handler y Repositorio Eloquent que lo requiera debe fijar explícitamente `protected $connection = 'pgsql_patrocinados';` en sus modelos — **nunca dejar que hereden la conexión `default` (MySQL)**.

---

## 2. Configuración de la conexión PostgreSQL

`config/database.php` ya trae un bloque `pgsql` genérico sin usar. En vez de reconfigurarlo como conexión primaria, se agrega una conexión nombrada dedicada para no arriesgar que algún modelo mentabit la tome por defecto:

```php
// config/database.php → dentro de 'connections'
'pgsql_patrocinados' => [
    'driver' => 'pgsql',
    'url' => env('PATROCINADOS_DB_URL'),
    'host' => env('PATROCINADOS_DB_HOST', '127.0.0.1'),
    'port' => env('PATROCINADOS_DB_PORT', '5432'),
    'database' => env('PATROCINADOS_DB_DATABASE', 'patrocinados'),
    'username' => env('PATROCINADOS_DB_USERNAME', 'postgres'),
    'password' => env('PATROCINADOS_DB_PASSWORD', ''),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => env('PATROCINADOS_DB_SSLMODE', 'prefer'),
],
```

Requisitos de infraestructura:
- Extensión `postgis` habilitada en la BD (`CREATE EXTENSION IF NOT EXISTS postgis;`) — debe ir en la **primera migración** del módulo, antes de cualquier tabla con columna `GEOGRAPHY`.
- Extensión `pgcrypto` o `uuid-ossp` para `gen_random_uuid()` / `uuid_generate_v4()` si se generan UUIDs en el motor en vez de en PHP (`Str::uuid()`). Recomendado: **generar el UUID en PHP** (como ya hace el resto del proyecto vía `HasUuids` de Laravel), así no depende de la extensión y es portable entre entornos de test (sqlite) y producción.
- `.env` nuevo: `PATROCINADOS_DB_HOST`, `PATROCINADOS_DB_PORT`, `PATROCINADOS_DB_DATABASE`, `PATROCINADOS_DB_USERNAME`, `PATROCINADOS_DB_PASSWORD`.
- Las migraciones del módulo deben ir en un path separado (p.ej. `database/migrations/patrocinados/`) y correrse con `php artisan migrate --path=database/migrations/patrocinados --database=pgsql_patrocinados`, igual que ya se hace con el filtro `cenefco`. Añadir el comando equivalente a esta sección de `CLAUDE.md` cuando se implemente.
- Tests: como el resto del proyecto usa `RefreshDatabase` contra la conexión default, los Feature Tests de este módulo deben apuntar explícitamente a `pgsql_patrocinados` (o a un sqlite en memoria con extensión spatial simulada/mockeada — a decidir en implementación, ver §7).

---

## 3. Mapeo de tablas → módulos DDD

Siguiendo la convención de `CLAUDE.md` (`Domain/{Modulo}`, `Application/{Modulo}`, `Infrastructure/{Modulo}`), se agrupan las 25 tablas del documento en **6 módulos** por cohesión funcional, no una carpeta por tabla:

| # | Módulo DDD | Tablas que agrupa | Motivo |
|---|---|---|---|
| 1 | **AccesoPatrocinados** | `usuarios`, `roles`, `permisos`, `usuarios_roles`, `roles_permisos` | Auth/autorización propia del módulo — es su propio sub-sistema, análogo a `Usuarios` de mentabit pero aislado |
| 2 | **Geografia** | `departamento`, `municipios`, `comunidades`, `ubicaciones` | Catálogo jerárquico territorial, reutilizado por Patrocinados y por Visitas |
| 3 | **Dispositivos** | `dispositivos` | Registro/habilitación de equipos móviles — entidad propia por su ciclo de vida (pendiente → activo → revocado) |
| 4 | **Patrocinados** | `estados_patrocinados`, `patrocinados`, `tipos_parentescos`, `tutores`, `historial_ubicaciones` | Entidad raíz del dominio (el niño/niña patrocinado) + sus relaciones directas |
| 5 | **Visitas** | `motivos_visitas`, `categorias_observaciones`, `planes_visitas`, `visitas`, `asignaciones_visitas`, `habilitaciones_visitas`, `ubicaciones_visitas`, `observaciones_visitas`, `fotos_visitas`, `revisiones_visitas` | Núcleo operativo: planificación, ejecución en campo, evidencia y revisión de cada visita |
| 6 | **Sincronizacion** | `lotes_sincronizacion`, `elementos_sincronizacion` | Motor de sync offline Flutter ↔ API, independiente del resto |

`registros_auditoria` (tabla 25) **no es un módulo de negocio**: es infraestructura transversal. Se implementa como un `AuditoriaService` de Application (o un Listener de eventos Eloquent) en `Application/Shared/` o `Application/Auditoria/`, escrito por todos los Handlers de escritura de los 6 módulos — no por cada módulo individualmente, para no duplicar la lógica de "qué se audita".

Carpetas resultantes (ejemplo módulo 4, el patrón se repite en los otros 5):

```text
Domain/Patrocinados/
  Contracts/
    PatrocinadoRepositoryInterface.php
    TutorRepositoryInterface.php
    EstadoPatrocinadoRepositoryInterface.php
    TipoParentescoRepositoryInterface.php
    HistorialUbicacionRepositoryInterface.php
  Exceptions/
    PatrocinadoNotFoundException.php
    TutorNotFoundException.php

Application/Patrocinados/
  DTOs/ Commands/ Handlers/ Queries/ QueryHandlers/

Infrastructure/Patrocinados/
  Models/
    Patrocinado.php        → protected $connection = 'pgsql_patrocinados';
    Tutor.php
    EstadoPatrocinado.php
    TipoParentesco.php
    HistorialUbicacion.php
  Repositories/
    EloquentPatrocinadoRepository.php
    ...
```

---

## 4. Orden de implementación (dependencias FK)

Las migraciones y el desarrollo deben respetar este orden (igual que indica el propio documento en "Notas para implementación"):

```text
Fase 0 — Infraestructura
  0.1  CREATE EXTENSION postgis;
  0.2  conexión pgsql_patrocinados configurada + .env

Fase 1 — Acceso (sin dependencias externas)
  1.1  roles
  1.2  permisos
  1.3  usuarios                 (FK a sí misma vía updated_by, nullable — crear sin FK, agregarla después o hacerla self-referencing nullable)
  1.4  usuarios_roles           (usuarios, roles)
  1.5  roles_permisos           (roles, permisos)

Fase 2 — Geografía
  2.1  departamento
  2.2  municipios               (departamento)
  2.3  comunidades              (municipios)
  2.4  ubicaciones              (comunidades)              ← requiere PostGIS ya creado

Fase 3 — Dispositivos
  3.1  dispositivos             (usuarios)

Fase 4 — Patrocinados
  4.1  estados_patrocinados
  4.2  patrocinados             (comunidades, ubicaciones, estados_patrocinados)
  4.3  tipos_parentescos
  4.4  tutores                  (patrocinados)
  4.5  historial_ubicaciones    (patrocinados, comunidades, ubicaciones, usuarios)

Fase 5 — Visitas
  5.1  motivos_visitas
  5.2  categorias_observaciones
  5.3  planes_visitas           (usuarios)
  5.4  visitas                  (planes_visitas, patrocinados, usuarios, motivos_visitas)
  5.5  asignaciones_visitas     (visitas, usuarios)
  5.6  habilitaciones_visitas   (visitas, usuarios, dispositivos)
  5.7  ubicaciones_visitas      (visitas, dispositivos, usuarios)
  5.8  observaciones_visitas    (visitas, categorias_observaciones, usuarios)
  5.9  fotos_visitas            (visitas)
  5.10 revisiones_visitas       (visitas, usuarios)

Fase 6 — Sincronización
  6.1  lotes_sincronizacion     (dispositivos, usuarios)
  6.2  elementos_sincronizacion (lotes_sincronizacion)

Fase 7 — Auditoría transversal
  7.1  registros_auditoria      (usuarios, dispositivos — ambas nullable)
```

`tipos_parentescos` puede adelantarse a la Fase 1 o 2 si se prefiere (no tiene dependencias), pero conviene crearla justo antes de `tutores` para mantener el diff de migraciones legible.

---

## 5. Puntos ambiguos o incompletos del documento fuente

Antes de generar migraciones reales, resolver estos vacíos (afectan el schema, no solo el código):

1. **`tutores.Parentesco` es VARCHAR libre, pero además existe `tipos_parentescos` como catálogo.** El documento no define FK entre ambas — hoy son redundantes. Decidir: ¿`tutores` referencia `tipos_parentescos.id` (recomendado, consistente con el resto del diseño catalogado) o el campo VARCHAR queda como está y `tipos_parentescos` es solo informativo/futuro? *(Bloquea: migración de `tutores`.)*
2. **`usuarios.updated_by` es auto-referencia FK a `usuarios.id`.** La primera fila de `usuarios` no puede tener `updated_by` poblado (no existe usuario previo) → debe ser `NULLABLE` desde el inicio (el doc ya lo marca NULLABLE, solo confirmar que Eloquent no la exige en el seeder inicial).
3. **`visitas.estado` vs `estados_patrocinados.Estado`**: son conceptos distintos con nombres parecidos — `estados_patrocinados` es el estado del *niño* (activo/mayor de edad/no encontrado) y `visitas.Estado` es el estado de *la visita* (PLANIFICADA/…). Ya están correctamente separados en el documento; solo dejar constancia para no confundir al nombrar Enums/Value Objects.
4. **Regla de negocio de reprogramación** (nota bajo tabla `visitas`: *"si el patrocinado no es encontrado se reprograma a 3 meses, si no se encuentra se decide si reprogramar de nuevo o dar de baja"*) no tiene columnas que la soporten explícitamente (¿contador de intentos? ¿fecha de próxima reprogramación? ¿quién decide la baja?). Esto es lógica de `Application/Visitas/Handlers/ReprogramarVisitaHandler.php`, pero probablemente falta un campo `intentos_reprogramacion SMALLINT` o similar en `visitas`, y el "dar de baja" apunta a `estados_patrocinados` (¿nuevo estado "inactivo por no ubicado"?). **Definir con el usuario/negocio antes de migrar `visitas` y `estados_patrocinados`.**
5. **Valores de dominio no enumerados**: varios campos usan `VARCHAR` con un valor DEFAULT documentado pero sin lista cerrada de valores válidos (`usuarios.estado`, `dispositivos.Estado`, `visitas.Estado`, `visitas.estado_revision`, `visitas.estado_sincronizacion`, `habilitaciones_visitas.Estado`, `lotes_sincronizacion.estado`, `elementos_sincronizacion.estado`, `elementos_sincronizacion.operacion`, `fotos_visitas` no tiene pero `ubicaciones_visitas.Fuente`). Para cada uno hay que fijar el enum completo antes de escribir el `CHECK constraint` de Postgres (recomendado en vez de dejarlo solo a nivel de validación de Laravel, dado que hay una app Flutter offline escribiendo directo eventualmente vía sync).
6. **`GEOGRAPHY(POINT,4326)` vs `Latitude/Longitude` planos**: `ubicaciones`, `ubicaciones_visitas` y `fotos_visitas` guardan ambos. Confirmar que el punto PostGIS se deriva siempre de lat/lng en el mismo INSERT/UPDATE (vía trigger `ST_MakePoint` o seteado explícito en el Repository) — si se llegan a desincronizar, las búsquedas espaciales (`ST_DWithin`, etc.) quedan inconsistentes con lo que ve el usuario en pantalla.
7. **Cifrado de fotos** (`fotos_visitas.Cifrada BOOLEAN DEFAULT TRUE`): el documento dice "almacenamiento privado" + hash SHA-256, pero no especifica el mecanismo de cifrado (¿at-rest del disco/bucket, o cifrado de archivo aplicativo con clave por foto?). Afecta el diseño de `FotoVisitaService` y de dónde vive la clave de cifrado.
8. **Guard de autenticación propio**: al ser `usuarios`/`roles`/`permisos` independientes de Sanctum+`t_usuario`, definir si el módulo usa un segundo guard Sanctum (tokens con `tokenable_type` distinto) o su propio sistema de tokens. Sanctum multi-modelo es viable (`HasApiTokens` en el nuevo modelo `Usuario` de Patrocinados) pero hay que decidir el prefijo de rutas (`/api/v1/patrocinados/auth/login` vs compartir `/api/v1/auth/login` con discriminación por rol — **no recomendado**, mejor namespacing separado).

---

## 6. Convenciones a aplicar (heredadas de `CLAUDE.md`, adaptadas)

- **PK**: `HasUuids` trait de Laravel (`$model->uuid` autogenerado en PHP), no `uuid_generate_v4()` de Postgres — mantiene paridad con SQLite en tests.
- **SoftDeletes**: solo en las tablas que el documento marca explícitamente con `deleted_at`: `usuarios`, `estados_patrocinados`, `patrocinados`, `tutores`, `visitas`, `fotos_visitas`. **`historial_ubicaciones` no tiene `deleted_at` en el documento** — no agregarlo por analogía con `patrocinados`. No agregar soft deletes donde el doc no lo pide.
- **DTOs**: nunca exponer el modelo Eloquent crudo; igual que `NoticiaDTO::fromModel()`, cada entidad tiene su DTO. Para `Patrocinado`, cuidado especial: es dato de un menor de edad — el DTO usado en listados/exportes debe permitir omitir campos sensibles (dirección, tutor, fecha nacimiento exacta) según el permiso del que consulta.
- **Transacciones**: todo Handler que toque `visitas` + una tabla dependiente (`asignaciones_visitas`, `habilitaciones_visitas`, `observaciones_visitas`, `fotos_visitas`) en la misma operación usa `DB::transaction()` sobre la conexión `pgsql_patrocinados` explícitamente: `DB::connection('pgsql_patrocinados')->transaction(...)`.
- **Auditoría**: cada Handler de escritura de los 6 módulos llama al `AuditoriaService` (tabla `registros_auditoria`) con `valores_anteriores`/`valores_nuevos` en JSON — igual patrón que `t_pagolog` en el módulo Pagos, pero genérico en vez de una tabla log por entidad.
- **Permisos**: el middleware `permiso:recurso.accion` existente es específico del sistema legado (`t_grupopermiso`/`t_permiso`). Para este módulo se necesita un middleware equivalente pero resuelto contra las tablas `roles`/`permisos`/`usuarios_roles`/`roles_permisos` propias — no reutilizar el middleware actual tal cual, aunque sí puede clonarse su forma (`permiso-patrocinados:visitas.crear`, por ejemplo).

---

## 7. Riesgos técnicos a validar en un spike antes de implementar en serio

1. **PostGIS en el entorno de desarrollo/CI**: confirmar que la imagen/contenedor Postgres usada en local y CI trae PostGIS instalado (`postgis/postgis` en Docker, o instalar la extensión en el Postgres gestionado si es RDS/Cloud SQL — algunos planes básicos no la incluyen).
2. **Testing sin Postgres real**: Laravel con SQLite in-memory no soporta `GEOGRAPHY`/PostGIS. Decidir estrategia de test: (a) Postgres real en CI vía servicio Docker, o (b) mockear el repositorio en los tests unitarios de Handlers (ya es el patrón para Handlers per `CLAUDE.md`) y reservar Postgres real solo para Feature Tests del módulo Visitas/Ubicaciones.
3. **Doble conexión + `DB::transaction()` cruzado**: si algún Handler futuro necesita escribir en mentabit (MySQL) y Patrocinados (Postgres) en la misma operación de negocio, **no hay transacción distribuida** entre ambos motores — hay que diseñar esos flujos (si existen) como eventual-consistency explícita, nunca asumir atomicidad cross-DB.
4. **Sincronización offline / conflictos**: `elementos_sincronizacion` registra `hash_datos` y `estado`, pero el documento no define la política de resolución de conflictos (last-write-wins por `visitas.Version`, o revisión manual). Esto es coordinación con el equipo Flutter antes de fijar el contrato de la API de sync.

---

## 8. Evaluación del diseño relacional (opinión técnica)

**Veredicto general: el modelo relacional está bien planteado.** Está correctamente normalizado (3FN en la práctica), la jerarquía de entidades sigue el flujo real del negocio (geografía → patrocinado → visita → evidencia/revisión de la visita → sync/auditoría), las cardinalidades declaradas (1:N) coinciden con la realidad del dominio, y hay decisiones de diseño que muestran que se pensó específicamente para un caso offline-first con implicaciones legales (menores de edad):

**Aciertos a destacar:**
- **Catálogos separados de entidades transaccionales** (`departamento`/`municipios`/`comunidades` vs `patrocinados`; `motivos_visitas`/`categorias_observaciones` vs `visitas`) — evita strings mágicos y permite habilitar/deshabilitar valores sin tocar datos históricos.
- **`historial_ubicaciones`** como tabla de historia append-only (con `fecha_inicio`/`fecha_fin`) en vez de sobrescribir `patrocinados.comunidad_id` — correcto para poder reconstruir "dónde vivía el niño en la fecha X", que es justo el tipo de dato que una auditoría de ONG necesita.
- **`habilitaciones_visitas`** con `fecha_expiracion` + `revoked_by` — modela bien el requisito de "acceso temporal revocable" del dispositivo en campo, en vez de un simple flag on/off.
- **`visitas.Version`** (versión optimista) + `estado_sincronizacion` — es el patrón correcto para resolver conflictos de escritura concurrente entre app offline y servidor; sin esto, un sync offline real es muy difícil de hacer bien.
- **`fotos_visitas`**: no guarda el binario en la BD, solo la referencia + `hash_sha256` — correcto para no acoplar el motor relacional al almacenamiento de archivos, y el hash permite detectar corrupción/alteración.
- **`registros_auditoria` con PK `BIGINT` en vez de UUID** (única excepción a la regla UUID del resto del modelo) — es la decisión correcta, no un descuido: es una tabla insert-only de alto volumen, y un PK secuencial evita la fragmentación de índice que un UUID aleatorio causaría ahí.

**Puntos débiles reales (no solo vacíos de documentación, sino riesgo de inconsistencia de datos):**

1. **Patrón recurrente "estado actual duplicado + tabla de historial", sin regla de sincronía explícita.** Aparece cuatro veces y en ninguna el documento dice quién actualiza a quién:
   - `patrocinados.comunidad_id`/`ubicacion_id` (estado actual) vs `historial_ubicaciones` (histórico). ¿Se actualiza el campo en `patrocinados` automáticamente cuando se cierra una fila de `historial_ubicaciones`, o pueden desincronizarse?
   - `visitas.user_id` (técnico asignado, campo suelto en `visitas`) vs `asignaciones_visitas` (histórico de asignaciones con `assigned_by`/`fecha_desasignacion`). Si se reasigna una visita, ¿el Handler está obligado a actualizar ambas tablas en la misma transacción?
   - `visitas.estado_revision` (campo en `visitas`) vs `revisiones_visitas` (historial de revisiones). No hay FK directa de `visitas` a "su revisión vigente"; `estado_revision` es un cache que alguien tiene que mantener al día.
   - `dispositivos.Estado` (campo suelto) vs `habilitaciones_visitas` (historial de habilitación/revocación por visita+dispositivo). Un dispositivo podría quedar "ACTIVO" en `dispositivos` mientras su última habilitación ya expiró.

   Esto no invalida el diseño — es un patrón legítimo (denormalización controlada por performance de lectura) — pero **hay que decidir explícitamente el mecanismo** que mantiene la coherencia: (a) el Handler actualiza ambos lados en la misma transacción (recomendado, ya es la convención del proyecto — ver `PagoCalculadorService` como única fuente de verdad en el módulo Pagos), o (b) un trigger de Postgres, o (c) el campo "actual" se elimina y se calcula siempre con una subquery a la tabla de historial (más simple, algo más lento). Sin elegir una de las tres, cada Handler nuevo puede implementarlo distinto y el dato se corrompe con el tiempo.

2. **Falta de restricción que garantice una sola fila "activa" por entidad en las tablas de historial/asignación.** Nada en el documento impide que `asignaciones_visitas` tenga dos filas con `estado = TRUE` para la misma `visita_id`, o que `habilitaciones_visitas` tenga dos habilitaciones activas simultáneas para la misma `visita_id`+`dispositivo_id`. En Postgres esto se resuelve barato con un índice único parcial, por ejemplo:
   ```sql
   CREATE UNIQUE INDEX uq_asignacion_activa
     ON asignaciones_visitas (visita_id) WHERE estado = TRUE;
   ```
   Recomendado agregarlo en la migración de `asignaciones_visitas` y de `habilitaciones_visitas` (esta última sobre `visita_id, dispositivo_id` con `estado = 'ACTIVA'`).

3. **Asimetría en el tratamiento de reasignación**: `patrocinados` sí tiene tabla de historial para cambios de ubicación (`historial_ubicaciones`), pero `dispositivos.user_id` no tiene equivalente — si un dispositivo pasa de un técnico a otro no queda rastro de quién lo tuvo antes. Dado que el resto del diseño es cuidadoso con la trazabilidad (hay `registros_auditoria` general), esto probablemente se resuelve solo con el log genérico de auditoría y no necesita tabla propia — pero vale confirmarlo, porque es inconsistente con el nivel de detalle que sí se dio a `historial_ubicaciones` y `asignaciones_visitas`.

4. **Los demás puntos ya señalados en la sección 5** (`tutores`/`tipos_parentescos` sin FK, enums sin lista cerrada, lat/lng vs `GEOGRAPHY` sin regla de derivación) son, en el fondo, la misma familia de problema: datos redundantes sin una regla declarada de quién es la fuente de verdad. Vale la pena resolverlos todos juntos con el mismo criterio antes de escribir la primera migración, en vez de decidir caso por caso durante la implementación.

**En resumen:** no hay errores de modelado grave (no hay cardinalidades mal puestas, ni normalización rota, ni acoplamientos indebidos entre catálogos y transacciones). El riesgo está todo concentrado en un mismo tipo de decisión — campos de "estado actual" que conviven con tablas de historial — que se repite sin que el documento diga cómo se mantienen sincronizados. Cerrar eso con una regla única (recomendación: Handler transaccional actualiza ambos lados, igual que ya hace el proyecto en Pagos) antes de migrar evita retrabajo posterior.

---

## 9. Siguiente paso sugerido

1. Resolver los 8 puntos de la sección 5 con quien definió el documento (probablemente junto al cliente/ONG dueño del sistema).
2. Resolver el patrón "estado actual + historial" de la sección 8 con una regla única para las 4 parejas de tablas identificadas.
3. Responder los 4 riesgos de la sección 7 con un spike corto (medio día): levantar Postgres+PostGIS local, crear `pgsql_patrocinados`, migrar `ubicaciones` de prueba con `GEOGRAPHY`, confirmar que Eloquent puede leer/escribir el punto sin librería adicional (o si hace falta un paquete tipo `matanyadaev/laravel-eloquent-spatial`).
4. Empezar implementación por el **Módulo 1 (AccesoPatrocinados)** siguiendo el patrón canónico de `CLAUDE.md` (Noticias) — es el que desbloquea todo lo demás (todas las demás tablas tienen `updated_by`/`created_by` a `usuarios`).

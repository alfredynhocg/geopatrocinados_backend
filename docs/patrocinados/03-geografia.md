# Etapa 3 — Módulo Geografía

> Dependencias: Etapa 2 (necesita `usuarios.id` para `updated_by`).
> Puede implementarse en paralelo con la Etapa 4.
> Tablas: `departamento`, `municipios`, `comunidades`, `ubicaciones`.

## Objetivo

Catálogo territorial jerárquico + primera tabla con columna `GEOGRAPHY(POINT,4326)` del módulo — es donde se resuelve, de una vez y para las demás etapas, cómo se guarda/lee un punto PostGIS desde Eloquent.

## Decisión técnica a resolver aquí (bloquea `ubicaciones`)

Elegir el mecanismo de lectura/escritura del punto geográfico:

- **Opción A (recomendada para arrancar rápido)**: no usar librería adicional. El Repository construye el punto con `DB::raw("ST_MakePoint(?, ?)::geography")` al insertar/actualizar, y lo lee con `ST_X`/`ST_Y` seleccionados como columnas planas en el `select()`. Menos "mágico", más control, cero dependencias nuevas.
- **Opción B**: paquete `matanyadaev/laravel-eloquent-spatial` — da un tipo `Point` como cast de Eloquent, soporta scopes (`whereDistanceSphere`, etc.). Más ergonómico si se van a hacer muchas queries espaciales (ej. "ubicaciones a menos de 5km de X"), pero es una dependencia externa a evaluar (mantenimiento, compatibilidad con Laravel 12).

Confirmar con el spike de la sección 7 del plan de revisión antes de escribir el Repository. Este documento asume **Opción A** en los ejemplos siguientes; si se adopta B, ajustar el Repository sin tocar el Contract ni el DTO (la interfaz pública no cambia).

**Regla de derivación lat/lng ↔ GEOGRAPHY** (plan de revisión §5.6): el Repository es la única fuente de verdad. En cada `create()`/`update()` de `Ubicacion`, `punto_geografico` se recalcula siempre a partir de `latitude`/`longitude` recibidos — nunca se aceptan ambos por separado desde el request sin recomputar el punto.

## Migraciones (orden)

```text
1. departamento
2. municipios      (FK departamento_id)
3. comunidades     (FK municipio_id)
4. ubicaciones     (FK comunidad_id) ← requiere extensión PostGIS ya creada (Etapa 1)
```

Columnas a decidir antes de migrar:
- `municipios.codigo` y `comunidades.codigo`: `UNIQUE, NULL` — confirmar que "único" aplica solo entre no-nulos (comportamiento estándar de Postgres, múltiples NULL están permitidos en un índice único). Si se requiere que el código sea obligatorio en la práctica, considerar `NOT NULL` en vez de dejarlo opcional — a decidir con negocio.
- `ubicaciones.Tipo VARCHAR(50) NULL`: enum sugerido a validar con negocio: `DOMICILIO`, `ESCUELA`, `PUNTO_REFERENCIA`, `OTRO`.

## Estructura DDD

```text
Domain/Geografia/
  Contracts/
    DepartamentoRepositoryInterface.php
    MunicipioRepositoryInterface.php
    ComunidadRepositoryInterface.php
    UbicacionRepositoryInterface.php
  Exceptions/
    DepartamentoNotFoundException.php
    MunicipioNotFoundException.php
    ComunidadNotFoundException.php
    UbicacionNotFoundException.php

Application/Geografia/
  DTOs/
    DepartamentoDTO.php
    MunicipioDTO.php
    ComunidadDTO.php
    UbicacionDTO.php          ← incluye latitude/longitude planos, NO expone el WKB de PostGIS crudo
  Commands/  Handlers/  Queries/  QueryHandlers/   ← CRUD estándar para las 4 entidades

Infrastructure/Geografia/
  Models/
    Departamento.php
    Municipio.php
    Comunidad.php
    Ubicacion.php     ← accessor/mutator o Repository que traduce punto_geografico ↔ latitude/longitude
  Repositories/
    EloquentDepartamentoRepository.php
    EloquentMunicipioRepository.php
    EloquentComunidadRepository.php
    EloquentUbicacionRepository.php   ← contiene la lógica ST_MakePoint/ST_X/ST_Y

Http/
  Controllers/Api/Patrocinados/
    DepartamentoController.php
    MunicipioController.php
    ComunidadController.php
    UbicacionController.php
  Requests/Patrocinados/Geografia/
    StoreUbicacionRequest.php   ← valida latitude [-90,90] / longitude [-180,180]
    ...
```

## Endpoints

```text
GET/POST        /api/v1/patrocinados/departamentos
PUT/DELETE      /api/v1/patrocinados/departamentos/{id}
GET/POST        /api/v1/patrocinados/municipios?departamento_id=
PUT/DELETE      /api/v1/patrocinados/municipios/{id}
GET/POST        /api/v1/patrocinados/comunidades?municipio_id=
PUT/DELETE      /api/v1/patrocinados/comunidades/{id}
GET/POST        /api/v1/patrocinados/ubicaciones?comunidad_id=
PUT/DELETE      /api/v1/patrocinados/ubicaciones/{id}
```

Todos con `->middleware('permiso-patrocinados:geografia.ver|crear|editar|eliminar')`.

## Criterios de aceptación

- [ ] Insertar una `Ubicacion` con `latitude=-17.393800, longitude=-66.156900` y verificar con `SELECT ST_AsText(punto_geografico) FROM ubicaciones` que el punto coincide (orden lng/lat, no lat/lng — error clásico de PostGIS).
- [ ] Actualizar solo `latitude` de una ubicación existente y confirmar que `punto_geografico` se recalculó (test específico para la regla de derivación).
- [ ] Listado paginado de `municipios` filtrado por `departamento_id` funciona con `->paginate()`.
- [ ] Borrar un `departamento` con municipios asociados falla por FK (no hay cascade documentado en el docx — confirmar que es intencional; si negocio pide cascade, usar `onDelete('restrict')` explícito, no el default implícito).

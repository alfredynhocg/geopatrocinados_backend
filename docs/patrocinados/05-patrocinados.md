# Etapa 5 — Módulo Patrocinados

> Dependencias: Etapa 3 (Geografía), Etapa 2 (usuarios).
> Tablas: `estados_patrocinados`, `patrocinados`, `tipos_parentescos`, `tutores`, `historial_ubicaciones`.
> Es la entidad raíz del dominio — el niño/niña patrocinado.

## Decisiones de negocio a cerrar antes de migrar (plan de revisión §5.1 y §5.4)

1. **`tutores.Parentesco` (VARCHAR libre) vs `tipos_parentescos` (catálogo)**: se resuelve como **FK obligatoria** — `tutores.tipo_parentesco_id UUID NOT NULL REFERENCES tipos_parentescos(id)`, eliminando el campo VARCHAR libre del docx. Si negocio insiste en texto libre además del catálogo (ej. "otro: especifique"), agregar un campo adicional `parentesco_detalle VARCHAR(100) NULL` solo para el caso `tipos_parentescos.parentesco = 'OTRO'`.
2. **Regla de reprogramación de visitas que afecta el estado del patrocinado** (nota del docx bajo la tabla `visitas`, pero el efecto final cae en `estados_patrocinados`): definir con negocio los estados exactos de `estados_patrocinados` más allá de los 3 mencionados ("inactivo no encontrado", "activo", "mayor de edad"). Propuesta a validar:
   ```text
   ACTIVO
   NO_ENCONTRADO        ← tras la 1ra visita fallida, dentro de los 3 meses de reprogramación
   INACTIVO_NO_UBICADO  ← tras agotar reprogramaciones, decisión de dar de baja
   MAYOR_DE_EDAD        ← egreso natural del programa
   ```
   Este catálogo se siembra en `estados_patrocinados` en esta etapa aunque la *lógica* que dispara el cambio de estado (`ReprogramarVisitaHandler`) se implemente en la Etapa 6.
3. **Regla de sincronía `patrocinados` ↔ `historial_ubicaciones`** (hallazgo §8.1 del plan de revisión, decisión ya cerrada en la hoja de ruta): el Handler que cambia la ubicación de un patrocinado hace, en una sola transacción:
   - cierra la fila vigente de `historial_ubicaciones` (`fecha_fin = now()`) si existe,
   - inserta la nueva fila de `historial_ubicaciones` (`fecha_inicio = now()`, `fecha_fin = NULL`),
   - actualiza `patrocinados.comunidad_id`/`ubicacion_id` al mismo valor.
   Esto se implementa **una sola vez** en `Application/Patrocinados/Handlers/CambiarUbicacionPatrocinadoHandler.php` — nunca se actualiza `patrocinados.comunidad_id` directamente desde `UpdatePatrocinadoHandler` (ese Handler debe rechazar cambios a `comunidad_id`/`ubicacion_id`, redirigiendo a este Handler dedicado).

## Migraciones (orden)

```text
1. estados_patrocinados
2. tipos_parentescos               (sin dependencias, se adelanta aquí por conveniencia)
3. patrocinados                    (FK comunidad_id, ubicacion_id NULLABLE, estado_id)
4. tutores                         (FK patrocinado_id, FK tipo_parentesco_id — ver decisión 1)
5. historial_ubicaciones           (FK patrocinado_id, comunidad_id, ubicacion_id NULLABLE, created_by)
```

## Estructura DDD

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
    EstadoPatrocinadoNotFoundException.php

Application/Patrocinados/
  DTOs/
    PatrocinadoDTO.php          ← DOS variantes de exposición, ver "Datos sensibles" abajo
    PatrocinadoResumenDTO.php   ← para listados: sin dirección de tutor ni fecha de nacimiento exacta
    TutorDTO.php
    EstadoPatrocinadoDTO.php
    TipoParentescoDTO.php
    HistorialUbicacionDTO.php
  Commands/
    CreatePatrocinadoCommand.php / UpdatePatrocinadoCommand.php / DeletePatrocinadoCommand.php
    CambiarUbicacionPatrocinadoCommand.php   ← el único camino válido para mover a un patrocinado
    CreateTutorCommand.php / UpdateTutorCommand.php / DeleteTutorCommand.php
    CreateEstadoPatrocinadoCommand.php / ...
    CreateTipoParentescoCommand.php / ...
  Handlers/
    CambiarUbicacionPatrocinadoHandler.php   ← implementa la regla de sincronía (ver arriba)
    (resto: CRUD estándar, 1 Handler por Command)
  Queries/
    GetPatrocinadosQuery.php     ← paginado, filtro por comunidad_id, estado_id, nivel_educativo
    GetPatrocinadoByIdQuery.php
    GetHistorialUbicacionesQuery.php  ← por patrocinado_id, para timeline en el frontend
  QueryHandlers/

Infrastructure/Patrocinados/
  Models/
    EstadoPatrocinado.php
    Patrocinado.php      → SoftDeletes
    TipoParentesco.php
    Tutor.php            → SoftDeletes
    HistorialUbicacion.php
  Repositories/
    EloquentPatrocinadoRepository.php    ← método propio moverUbicacion() usado solo por el Handler de cambio de ubicación
    EloquentTutorRepository.php
    EloquentEstadoPatrocinadoRepository.php
    EloquentTipoParentescoRepository.php
    EloquentHistorialUbicacionRepository.php

Http/
  Controllers/Api/Patrocinados/
    PatrocinadoController.php
    TutorController.php
    EstadoPatrocinadoController.php
    TipoParentescoController.php
  Requests/Patrocinados/Patrocinados/
    StorePatrocinadoRequest.php / UpdatePatrocinadoRequest.php
    CambiarUbicacionPatrocinadoRequest.php
    StoreTutorRequest.php / UpdateTutorRequest.php
```

## Datos sensibles (menor de edad) — regla de exposición

`PatrocinadoDTO::fromModel()` completo (con tutor, dirección exacta, fecha de nacimiento) solo se devuelve si el usuario autenticado tiene el permiso `patrocinados.ver-detalle`. Los listados (`GetPatrocinadosQuery`) usan `PatrocinadoResumenDTO` por defecto — sin nombre completo del tutor ni dirección, con edad aproximada en vez de fecha de nacimiento exacta. Esto se resuelve en el `QueryHandler`, no en el Controller (el Controller no debe decidir qué DTO usar; recibe el permiso resuelto o el `QueryHandler` lo consulta vía `Gate`/`auth()->user()`).

## Endpoints

```text
GET/POST      /api/v1/patrocinados/ninos                          (tabla `patrocinados`)
GET/PUT/DELETE /api/v1/patrocinados/ninos/{id}
POST          /api/v1/patrocinados/ninos/{id}/cambiar-ubicacion
GET           /api/v1/patrocinados/ninos/{id}/historial-ubicaciones
GET/POST      /api/v1/patrocinados/ninos/{id}/tutores
PUT/DELETE    /api/v1/patrocinados/tutores/{id}
GET/POST      /api/v1/patrocinados/estados-patrocinados
GET/POST      /api/v1/patrocinados/tipos-parentescos
```

## Criterios de aceptación

- [ ] `CambiarUbicacionPatrocinadoHandler` deja exactamente una fila con `fecha_fin = NULL` en `historial_ubicaciones` por patrocinado tras cada operación (test que ejecuta 3 cambios seguidos y verifica el conteo).
- [ ] `UpdatePatrocinadoHandler` rechaza (422 o simplemente ignora) cualquier intento de modificar `comunidad_id`/`ubicacion_id` fuera del Handler dedicado.
- [ ] Un usuario sin `patrocinados.ver-detalle` recibe `PatrocinadoResumenDTO` en el listado, verificado por test de Feature comparando las claves del JSON.
- [ ] Borrar un `patrocinado` es soft-delete; sus `tutores` e `historial_ubicaciones` permanecen consultables para auditoría.

## Código completo

Ver [codigo/05-patrocinados-codigo.md](codigo/05-patrocinados-codigo.md) — código PHP completo de cada archivo de la Estructura DDD de esta etapa.

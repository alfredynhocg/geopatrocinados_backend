# Etapa 6 — Módulo Visitas

> Dependencias: Etapa 5 (Patrocinados), Etapa 4 (Dispositivos), Etapa 3 (Geografía indirecta vía Patrocinados), Etapa 2 (usuarios).
> Es el núcleo operativo del sistema — la etapa más grande, se divide en 4 sub-etapas para poder entregarlas y testearlas por separado.
> Tablas (10): `motivos_visitas`, `categorias_observaciones`, `planes_visitas`, `visitas`, `asignaciones_visitas`, `habilitaciones_visitas`, `ubicaciones_visitas`, `observaciones_visitas`, `fotos_visitas`, `revisiones_visitas`.

## Decisiones de negocio a cerrar antes de migrar

1. **Regla completa de reprogramación** (plan de revisión §5.4 — el punto más importante pendiente de todo el módulo). El docx dice: *"si el patrocinado no es encontrado se reprograma a 3 meses; si aún no se lo encuentra se define si se vuelve a reprogramar o se da de baja"*. Falta modelar:
   - ¿Cuántos ciclos de reprogramación se permiten antes de forzar la decisión de baja? (agregar `visitas.intentos_reprogramacion SMALLINT DEFAULT 0` si no existe ya un contador equivalente).
   - ¿Quién decide "reprogramar de nuevo" vs "dar de baja"? (¿el técnico en campo, o un supervisor en `revisiones_visitas`?) — probablemente es una decisión de supervisor, lo que ata esta regla a la Sub-etapa 6d (Revisión).
   - Al dar de baja, ¿qué `estados_patrocinados` se asigna? (ver Etapa 5, decisión 2: `INACTIVO_NO_UBICADO`).
   **Este punto bloquea el diseño final de `visitas` y de `ReprogramarVisitaHandler` — no migrar `visitas` hasta cerrarlo.**
2. **Enums cerrados** (plan de revisión §5.5) — definir antes de escribir los `CHECK constraints`:
   ```text
   visitas.Estado:                PLANIFICADA, EN_CURSO, FINALIZADA, NO_ENCONTRADO, REPROGRAMADA, CANCELADA
   visitas.estado_revision:       PENDIENTE, APROBADA, RECHAZADA, REQUIERE_CORRECCION
   visitas.estado_sincronizacion: PENDIENTE, SINCRONIZADO, ERROR
   habilitaciones_visitas.Estado: ACTIVA, EXPIRADA, REVOCADA
   ubicaciones_visitas.Fuente:    GPS, RED, MANUAL
   ```
3. **Regla de sincronía `visitas.user_id` ↔ `asignaciones_visitas`** (hallazgo §8.1, decisión ya cerrada en la hoja de ruta): toda reasignación pasa por `Application/Visitas/Handlers/ReasignarVisitaHandler.php`, que en una sola transacción: cierra la asignación activa (`fecha_desasignacion = now()`, `estado = false`), crea la nueva fila en `asignaciones_visitas` (`estado = true`), y actualiza `visitas.user_id`. Se agrega el índice único parcial recomendado en el plan de revisión §8.2.
4. **Regla de sincronía `visitas.estado_revision` ↔ `revisiones_visitas`**: `Application/Visitas/Handlers/RevisarVisitaHandler.php` inserta la fila en `revisiones_visitas` y actualiza `visitas.estado_revision` en la misma transacción — nunca se infiere el estado de revisión leyendo la última fila de `revisiones_visitas` en tiempo real (evitar N+1 y evitar el riesgo de lecturas inconsistentes bajo concurrencia).
5. **Regla de sincronía `dispositivos.Estado` ↔ `habilitaciones_visitas`**: al revocar una habilitación no se toca `dispositivos.Estado` (son conceptos distintos: un dispositivo puede seguir `ACTIVO` con una habilitación de visita puntual `REVOCADA`). Solo `RevocarDispositivoHandler` (Etapa 4) cambia `dispositivos.Estado`. Documentado aquí para que quien implemente `habilitaciones_visitas` no toque `dispositivos` por error.

## Migraciones (orden)

```text
Sub-etapa 6a — Planificación
  1. motivos_visitas
  2. categorias_observaciones
  3. planes_visitas                (FK created_by → usuarios)
  4. visitas                       (FK plan_visita_id NULLABLE, patrocinado_id, user_id, motivo_visita_id NULLABLE)
     + índice: (patrocinado_id, estado) para queries de "visitas pendientes de un niño"

Sub-etapa 6b — Asignación y habilitación
  5. asignaciones_visitas          (FK visita_id, tecnico_id, assigned_by)
     + UNIQUE INDEX parcial (visita_id) WHERE estado = TRUE
  6. habilitaciones_visitas        (FK visita_id, tecnico_id, dispositivo_id, authorized_by, revoked_by NULLABLE)
     + UNIQUE INDEX parcial (visita_id, dispositivo_id) WHERE "Estado" = 'ACTIVA'

Sub-etapa 6c — Evidencia de campo
  7. ubicaciones_visitas           (FK visita_id, dispositivo_id, tecnico_id) — requiere PostGIS
  8. observaciones_visitas         (FK visita_id, categoria_id NULLABLE, created_by)
  9. fotos_visitas                 (FK visita_id)

Sub-etapa 6d — Revisión y cierre
  10. revisiones_visitas           (FK visita_id, user_id)
```

## Estructura DDD

```text
Domain/Visitas/
  Contracts/
    MotivoVisitaRepositoryInterface.php
    CategoriaObservacionRepositoryInterface.php
    PlanVisitaRepositoryInterface.php
    VisitaRepositoryInterface.php
    AsignacionVisitaRepositoryInterface.php
    HabilitacionVisitaRepositoryInterface.php
    UbicacionVisitaRepositoryInterface.php
    ObservacionVisitaRepositoryInterface.php
    FotoVisitaRepositoryInterface.php
    RevisionVisitaRepositoryInterface.php
  Exceptions/
    VisitaNotFoundException.php
    VisitaYaAsignadaException.php          ← 422, viola el índice único parcial de asignación activa
    DispositivoNoHabilitadoException.php   ← 403, intento de capturar evidencia sin habilitación activa
    HabilitacionExpiradaException.php      ← 403

Application/Visitas/
  DTOs/  (una por entidad, siguiendo el patrón NoticiaDTO::fromModel())
  Commands/
    CreatePlanVisitaCommand / UpdatePlanVisitaCommand
    CreateVisitaCommand / UpdateVisitaCommand
    ReasignarVisitaCommand              ← implementa la regla de sincronía #3
    IniciarVisitaCommand                ← fecha_inicio, valida habilitación activa del dispositivo
    FinalizarVisitaCommand              ← fecha_finalizacion, cambia Estado a FINALIZADA/NO_ENCONTRADO
    ReprogramarVisitaCommand            ← implementa la regla de negocio #1 (contador + decisión de baja)
    HabilitarDispositivoParaVisitaCommand
    RevocarHabilitacionVisitaCommand
    CapturarUbicacionVisitaCommand      ← ST_MakePoint igual que en Geografía (reusar el mismo mecanismo elegido en Etapa 3)
    CreateObservacionVisitaCommand
    SubirFotoVisitaCommand              ← delega el binario a un Storage privado, guarda clave+hash+metadatos
    RevisarVisitaCommand                ← implementa la regla de sincronía #4
  Handlers/                              ← uno por Command, todos con DB::connection('pgsql_patrocinados')->transaction()
  Services/
    FotoVisitaService.php                ← cifrado, hash SHA-256, ruta de almacenamiento privado (ver Etapa 9 para la decisión de mecanismo de cifrado pendiente del plan de revisión §5.7)
  Queries/
    GetVisitasQuery                      ← filtros: patrocinado_id, tecnico_id, estado, rango de fechas
    GetVisitaByIdQuery                   ← incluye asignación activa, habilitación activa, observaciones, fotos (metadatos), revisión vigente
    GetVisitasPendientesDeRevisionQuery
  QueryHandlers/

Infrastructure/Visitas/
  Models/  (una por tabla, con SoftDeletes solo en Visita y FotoVisita según el docx)
  Repositories/
    EloquentVisitaRepository.php         ← incluye reasignar(), iniciar(), finalizar(), reprogramar() como métodos de escritura específicos, no genéricos update()
    (resto: CRUD estándar)

Http/
  Controllers/Api/Patrocinados/
    PlanVisitaController.php
    VisitaController.php                 ← incluye acciones no-CRUD: reasignar, iniciar, finalizar, reprogramar
    HabilitacionVisitaController.php
    UbicacionVisitaController.php
    ObservacionVisitaController.php
    FotoVisitaController.php
    RevisionVisitaController.php
  Requests/Patrocinados/Visitas/
    (una por Command de arriba)
```

## Endpoints (resumen — el detalle completo de FormRequests se define al implementar)

```text
GET/POST        /api/v1/patrocinados/planes-visitas
GET/POST        /api/v1/patrocinados/visitas
GET/PUT         /api/v1/patrocinados/visitas/{id}
POST            /api/v1/patrocinados/visitas/{id}/reasignar
POST            /api/v1/patrocinados/visitas/{id}/iniciar
POST            /api/v1/patrocinados/visitas/{id}/finalizar
POST            /api/v1/patrocinados/visitas/{id}/reprogramar
POST            /api/v1/patrocinados/visitas/{id}/habilitaciones      (habilitar dispositivo)
POST            /api/v1/patrocinados/habilitaciones-visitas/{id}/revocar
POST            /api/v1/patrocinados/visitas/{id}/ubicaciones          (captura GPS)
GET/POST        /api/v1/patrocinados/visitas/{id}/observaciones
POST            /api/v1/patrocinados/visitas/{id}/fotos
GET             /api/v1/patrocinados/visitas/{id}/fotos/{fotoId}       (URL firmada temporal, no la clave directa de storage)
GET/POST        /api/v1/patrocinados/visitas/{id}/revisiones
```

Middleware relevante: las rutas de captura de evidencia (`ubicaciones`, `observaciones`, `fotos`) deben validar, antes que el permiso genérico, que existe una `habilitacion_visita` **ACTIVA y no expirada** para el `dispositivo_id` reportado por la app — esto va en un middleware o en el propio Handler (`DispositivoNoHabilitadoException`), es una regla de seguridad de negocio, no solo un permiso RBAC.

## Criterios de aceptación

- [ ] Intentar dos asignaciones activas simultáneas para la misma visita falla por el índice único parcial (test que verifica el 422 mapeado desde `VisitaYaAsignadaException`, no un 500 de constraint de Postgres sin capturar).
- [ ] `ReasignarVisitaHandler` deja `asignaciones_visitas` con una sola fila activa y `visitas.user_id` actualizado, verificado en el mismo test.
- [ ] Capturar una ubicación/observación/foto con un `dispositivo_id` sin habilitación activa devuelve 403 con `DispositivoNoHabilitadoException`.
- [ ] `RevisarVisitaHandler` deja `visitas.estado_revision` igual al `Estado` de la última fila insertada en `revisiones_visitas`.
- [ ] La regla de reprogramación (una vez cerrada con negocio) tiene su propio test de escenario completo: 1ra visita no encontrada → reprogramada a 3 meses → 2da no encontrada → decisión de baja → `estados_patrocinados` del niño actualizado.
- [ ] Fotos: el endpoint de listado nunca devuelve `clave_almacenamiento` cruda, solo una URL firmada de corta duración.

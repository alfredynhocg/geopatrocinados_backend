# Etapa 7 — Módulo Sincronización

> Dependencias: Etapa 4 (Dispositivos), y funcionalmente de todo lo que la app Flutter puede escribir offline (Etapas 5 y 6) — por eso va después, aunque su propio schema es simple.
> Tablas: `lotes_sincronizacion`, `elementos_sincronizacion`.

## Decisión de negocio a cerrar antes de implementar (plan de revisión §7.4)

Definir la **política de resolución de conflictos** con el equipo Flutter antes de fijar el contrato de la API de sync. Dos opciones razonables, elegir una:

- **Last-write-wins por `visitas.Version`**: el cliente manda su `Version` conocida; si no coincide con la del servidor, el elemento se marca `estado = 'ERROR'` en `elementos_sincronizacion` con `mensaje_error = 'conflicto_version'` y se descarta — el cliente debe refrescar y reintentar manualmente.
- **Cola de revisión manual**: los conflictos de versión no se descartan, quedan en un estado `CONFLICTO` visible para un supervisor que decide cuál versión prevalece.

Este documento asume **last-write-wins** en los ejemplos siguientes por ser más simple de implementar primero; si negocio requiere cola de revisión, es un cambio de Handler, no de schema.

## Migraciones (orden)

```text
1. lotes_sincronizacion       (FK dispositivo_id, user_id)
2. elementos_sincronizacion   (FK lote_sincronizacion_id)
```

Enum a cerrar antes de migrar:
```text
lotes_sincronizacion.estado:      SINCRONIZANDO, COMPLETADO, ERROR
elementos_sincronizacion.estado:  PENDIENTE, SINCRONIZADO, ERROR
elementos_sincronizacion.operacion: CREATE, UPDATE, DELETE
```

## Estructura DDD

```text
Domain/Sincronizacion/
  Contracts/
    LoteSincronizacionRepositoryInterface.php
    ElementoSincronizacionRepositoryInterface.php
  Exceptions/
    LoteSincronizacionNotFoundException.php
    ConflictoVersionException.php   ← se traduce a estado ERROR del elemento, no a un 5xx

Application/Sincronizacion/
  DTOs/
    LoteSincronizacionDTO.php
    ElementoSincronizacionDTO.php
    ResultadoSincronizacionDTO.php   ← resumen por lote: enviados/recibidos/errores, para que la app decida reintentos
  Commands/
    IniciarLoteSincronizacionCommand
    ProcesarElementoSincronizacionCommand   ← recibe tipo_entidad + entidad_id + operacion + payload, despacha al Handler del módulo correspondiente
    CerrarLoteSincronizacionCommand
  Handlers/
    IniciarLoteSincronizacionHandler
    ProcesarElementoSincronizacionHandler   ← el más importante: enruta cada elemento a Visitas/Patrocinados/etc. según tipo_entidad
    CerrarLoteSincronizacionHandler
  Services/
    SincronizacionRouterService.php   ← mapa tipo_entidad → Handler de destino (evita un switch gigante en el Handler)
  Queries/
    GetLotesSincronizacionQuery
    GetElementosPendientesQuery

Infrastructure/Sincronizacion/
  Models/
    LoteSincronizacion.php
    ElementoSincronizacion.php
  Repositories/
    EloquentLoteSincronizacionRepository.php
    EloquentElementoSincronizacionRepository.php

Http/
  Controllers/Api/Patrocinados/
    SincronizacionController.php
  Requests/Patrocinados/Sincronizacion/
    IniciarLoteRequest.php
    ProcesarElementoRequest.php
```

## Contrato de API de sync (a validar con el equipo Flutter antes de implementar)

```text
POST /api/v1/patrocinados/sincronizacion/lotes
  → body: { dispositivo_id }
  → 201: { lote_id, fecha_inicio }

POST /api/v1/patrocinados/sincronizacion/lotes/{loteId}/elementos
  → body: { tipo_entidad, entidad_id, operacion, hash_datos, payload }
  → por cada elemento: valida hash, despacha vía SincronizacionRouterService,
    responde 200 con { estado: SINCRONIZADO } o { estado: ERROR, mensaje_error }
  → NUNCA falla toda la request por un elemento individual — cada elemento se procesa
    y reporta su propio resultado (el batch es best-effort, no todo-o-nada)

POST /api/v1/patrocinados/sincronizacion/lotes/{loteId}/cerrar
  → body: { registros_enviados, registros_recibidos }
  → 200: ResultadoSincronizacionDTO
```

**Nota de diseño importante**: `ProcesarElementoSincronizacionHandler` NO debe envolver todos los elementos de un lote en una sola transacción — si un elemento falla (conflicto de versión, entidad no encontrada), los demás elementos del mismo lote deben poder sincronizar igual. Cada elemento es su propia unidad transaccional.

## Criterios de aceptación

- [ ] Un lote con 10 elementos donde 1 tiene conflicto de versión termina con 9 `SINCRONIZADO` y 1 `ERROR` — el lote se cierra igual, no se aborta completo.
- [ ] `ProcesarElementoSincronizacionHandler` con `tipo_entidad = 'visita'` y `operacion = 'UPDATE'` termina llamando efectivamente a los Handlers de la Etapa 6 (test de integración, no solo mock).
- [ ] Reintentar un elemento ya `SINCRONIZADO` (reenvío duplicado por reintento de red del cliente) es idempotente — no duplica el efecto (usar `entidad_id` + `hash_datos` para detectar reenvíos).

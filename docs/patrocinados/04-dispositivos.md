# Etapa 4 — Módulo Dispositivos

> Dependencias: Etapa 2 (`usuarios`).
> Puede implementarse en paralelo con la Etapa 3.
> Tabla: `dispositivos`.

## Objetivo

Registro y ciclo de vida de los equipos móviles (Flutter) que un técnico usa en campo: alta → pendiente de aprobación → activo → (opcional) revocado.

## Decisión de negocio a confirmar antes de migrar

`dispositivos.Estado VARCHAR(20) DEFAULT 'PENDIENTE'` — cerrar el enum completo. Mínimo viable según el flujo descrito en el docx (§ "Control de dispositivos, habilitación temporal... y revocación"):

```text
PENDIENTE   → recién registrado, requiere aprobación de un admin
ACTIVO      → aprobado, puede recibir habilitaciones de visita
REVOCADO    → acceso retirado (fecha_revocacion + revoked_by poblados)
```

Agregar `CHECK (estado IN ('PENDIENTE','ACTIVO','REVOCADO'))` en la migración.

**Nota del hallazgo §8.3 del plan de revisión**: a diferencia de `patrocinados` (que tiene `historial_ubicaciones`), `dispositivos` no tiene tabla de historial si un dispositivo cambia de dueño (`user_id`). Confirmado con negocio: **se resuelve solo con `registros_auditoria` (Etapa 8)**, no se crea una tabla de historial dedicada — un dispositivo reasignado es un evento de auditoría, no un caso de uso frecuente que justifique su propia tabla. Si esto cambia, reabrir el diseño antes de migrar.

## Migración

```text
1. dispositivos   (FK user_id → usuarios, FK revoked_by → usuarios NULLABLE)
```

## Estructura DDD

```text
Domain/Dispositivos/
  Contracts/
    DispositivoRepositoryInterface.php
  Exceptions/
    DispositivoNotFoundException.php
    DispositivoYaRegistradoException.php   ← 422, viola UNIQUE(identificador_dispositivo)
    DispositivoRevocadoException.php       ← 403, intento de uso de dispositivo revocado

Application/Dispositivos/
  DTOs/
    DispositivoDTO.php
  Commands/
    RegistrarDispositivoCommand.php   ← alta desde la app (estado inicial PENDIENTE)
    AprobarDispositivoCommand.php     ← PENDIENTE → ACTIVO
    RevocarDispositivoCommand.php     ← ACTIVO → REVOCADO (fecha_revocacion, revoked_by)
    UpdateDispositivoCommand.php      ← nombre, version_sistema, version_aplicacion
  Handlers/
    RegistrarDispositivoHandler.php
    AprobarDispositivoHandler.php
    RevocarDispositivoHandler.php
    UpdateDispositivoHandler.php
  Queries/
    GetDispositivosQuery.php          ← filtrable por user_id, estado
    GetDispositivoByIdQuery.php
  QueryHandlers/

Infrastructure/Dispositivos/
  Models/
    Dispositivo.php
  Repositories/
    EloquentDispositivoRepository.php

Http/
  Controllers/Api/Patrocinados/
    DispositivoController.php
  Requests/Patrocinados/Dispositivos/
    RegistrarDispositivoRequest.php
    AprobarDispositivoRequest.php
    RevocarDispositivoRequest.php
```

## Endpoints

```text
POST    /api/v1/patrocinados/dispositivos                 registrar (el propio usuario autenticado desde la app)
GET     /api/v1/patrocinados/dispositivos?user_id=&estado= listar (admin)
GET     /api/v1/patrocinados/dispositivos/{id}
PUT     /api/v1/patrocinados/dispositivos/{id}
POST    /api/v1/patrocinados/dispositivos/{id}/aprobar     permiso-patrocinados:dispositivos.aprobar
POST    /api/v1/patrocinados/dispositivos/{id}/revocar     permiso-patrocinados:dispositivos.revocar
```

## Criterios de aceptación

- [ ] Registrar el mismo `identificador_dispositivo` dos veces lanza `DispositivoYaRegistradoException` (422), no un error 500 de constraint violado sin capturar.
- [ ] Un dispositivo `REVOCADO` no puede solicitar una nueva `habilitacion_visita` (validación cruzada que se implementa/testea completa recién en la Etapa 6, pero el estado debe quedar bien modelado desde aquí).
- [ ] `AprobarDispositivoHandler` y `RevocarDispositivoHandler` registran en `registros_auditoria` (aunque el servicio genérico se construya recién en la Etapa 8 — dejar el `TODO` explícito y la llamada comentada si se implementa antes que la Etapa 8, o reordenar para hacer primero un `AuditoriaService` mínimo).

## Código completo

Ver [codigo/04-dispositivos-codigo.md](codigo/04-dispositivos-codigo.md) — código PHP completo de cada archivo de la Estructura DDD de esta etapa.

## Código completo

Ver [codigo/04-dispositivos-codigo.md](codigo/04-dispositivos-codigo.md) — código PHP completo de cada archivo de la Estructura DDD de esta etapa.

# Etapa 8 — Auditoría transversal

> Dependencia funcional: Etapa 2 (usuarios), Etapa 4 (dispositivos).
> Dependencia de integración: se conecta a los Handlers de escritura de **todas** las demás etapas — conviene construir el `AuditoriaService` apenas termine la Etapa 2 y no al final, para no retrofitear los Handlers de las Etapas 3-7 después de escritos. Este documento describe el servicio y su tabla; su *adopción* ocurre incrementalmente durante las Etapas 2-7.
> Tabla: `registros_auditoria`.

## Objetivo

Registro centralizado de cambios (`valores_anteriores`/`valores_nuevos` en JSONB) para las operaciones sensibles de los 6 módulos, sin duplicar la lógica de auditoría en cada uno — mismo espíritu que `t_pagolog` en el módulo Pagos de mentabit, pero genérico en vez de una tabla log por entidad.

## Migración

```text
1. registros_auditoria   (FK user_id NULLABLE, dispositivo_id NULLABLE)
```

Nota de diseño ya validada en el plan de revisión §8 (acierto, no pendiente): PK `BIGINT` autoincremental, **no UUID** — es la única excepción intencional a la regla de UUID del resto del módulo, por ser una tabla insert-only de alto volumen.

## Estructura DDD

```text
Domain/Auditoria/
  Contracts/
    RegistroAuditoriaRepositoryInterface.php
  Exceptions/
    (ninguna necesaria — es un módulo de solo escritura/lectura, sin reglas de negocio que fallen)

Application/Auditoria/
  DTOs/
    RegistroAuditoriaDTO.php
  Services/
    AuditoriaService.php   ← único punto de entrada, inyectado en los Handlers de escritura de los demás módulos
  Queries/
    GetRegistrosAuditoriaQuery.php   ← filtros: tipo_entidad, entidad_id, user_id, rango de fechas
  QueryHandlers/
    GetRegistrosAuditoriaQueryHandler.php

Infrastructure/Auditoria/
  Models/
    RegistroAuditoria.php
  Repositories/
    EloquentRegistroAuditoriaRepository.php

Http/
  Controllers/Api/Patrocinados/
    RegistroAuditoriaController.php   ← solo lectura (index/show), sin store/update/destroy
```

## Contrato del servicio

```php
// app/Application/Auditoria/Services/AuditoriaService.php
class AuditoriaService
{
    public function registrar(
        ?string $userId,
        ?string $dispositivoId,
        string $accion,        // 'crear' | 'editar' | 'eliminar' | 'aprobar' | 'revocar' | ...
        string $modulo,        // 'Visitas' | 'Patrocinados' | 'Dispositivos' | ...
        ?string $tipoEntidad,  // 'visita' | 'patrocinado' | 'dispositivo' | ...
        ?string $entidadId,
        ?array $valoresAnteriores,
        ?array $valoresNuevos,
    ): void {
        // insertGetId — nunca MAX(id)+1, igual regla que el resto del proyecto
    }
}
```

Uso dentro de cualquier Handler de escritura de las Etapas 2-7 (ejemplo con `RevocarDispositivoHandler`):

```php
public function handle(RevocarDispositivoCommand $command): DispositivoDTO
{
    return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
        $anterior = $this->repository->findById($command->dispositivoId);

        $model = $this->repository->revocar($command->dispositivoId, $command->revokedBy);

        $this->auditoria->registrar(
            userId: $command->revokedBy,
            dispositivoId: $command->dispositivoId,
            accion: 'revocar',
            modulo: 'Dispositivos',
            tipoEntidad: 'dispositivo',
            entidadId: $command->dispositivoId,
            valoresAnteriores: ['estado' => $anterior->estado],
            valoresNuevos: ['estado' => 'REVOCADO'],
        );

        return DispositivoDTO::fromModel($model);
    });
}
```

## Qué se audita (mínimo obligatorio por módulo)

| Módulo | Operaciones auditadas |
|---|---|
| AccesoPatrocinados | crear/editar/eliminar usuario, asignar/revocar rol, asignar/revocar permiso, login fallido tras bloqueo |
| Dispositivos | registrar, aprobar, revocar |
| Patrocinados | crear/editar/eliminar patrocinado, **cambiar ubicación** (con antes/después de comunidad_id/ubicacion_id) |
| Visitas | crear, reasignar, iniciar, finalizar, **reprogramar** (con antes/después de estado del patrocinado si aplica), habilitar/revocar dispositivo, revisar |
| Sincronizacion | (opcional — el propio `elementos_sincronizacion` ya es su historial; no duplicar) |

No es necesario auditar operaciones de solo lectura ni las de catálogos simples (`motivos_visitas`, `categorias_observaciones`, etc.) salvo que negocio lo pida explícitamente — mantener el volumen de la tabla acotado a lo que realmente importa para trazabilidad legal.

## Endpoints

```text
GET /api/v1/patrocinados/registros-auditoria?tipo_entidad=&entidad_id=&user_id=&desde=&hasta=
```
Solo lectura, `->middleware('permiso-patrocinados:auditoria.ver')` — típicamente restringido a rol `SUPERADMIN`/`SUPERVISOR`.

## Criterios de aceptación

- [ ] Cada Handler de la tabla "qué se audita" tiene un test que verifica la fila insertada en `registros_auditoria` con `valores_anteriores`/`valores_nuevos` correctos.
- [ ] `AuditoriaService::registrar()` nunca lanza una excepción que aborte la transacción de negocio (si falla el insert de auditoría, se loguea a `Log::error()` pero no revierte la operación principal — a decidir explícitamente: ¿es aceptable perder un registro de auditoría antes que bloquear una visita? Confirmar con negocio; si la respuesta es "no, la auditoría es obligatoria", entonces sí debe abortar la transacción).
- [ ] El endpoint de listado pagina correctamente con `->paginate()` sobre una tabla de alto volumen (verificar plan de query con `EXPLAIN` si el volumen esperado es alto desde el día 1).

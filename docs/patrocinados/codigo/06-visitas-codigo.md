# Código completo — Etapa 6: Visitas

> Fuente de columnas: `database/migrations/patrocinados/2026_09_01_0000{40..49}_*.php` (leídas literalmente).
> Convenciones: ver [../06-visitas.md](../06-visitas.md) — enums cerrados, reglas de sincronía, namespaces.
> Todos los modelos usan `App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados` (trait definido en Etapa 1) + `HasUuids`.
> Todos los Handlers de escritura auditados inyectan `App\Application\Auditoria\Services\AuditoriaService` (contrato completo en [../08-auditoria-transversal.md](../08-auditoria-transversal.md)) y llaman `registrar()` dentro de la misma transacción, según la tabla "qué se audita" de esa etapa.

---

## 6a — Planificación (`motivos_visitas`, `categorias_observaciones`, `planes_visitas`, `visitas`)

### Domain

#### app/Domain/Visitas/Contracts/MotivoVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface MotivoVisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;
    public function findById(string $id): mixed;
    public function create(array $data): mixed;
    public function update(string $id, array $data): mixed;
    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Visitas/Contracts/CategoriaObservacionRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface CategoriaObservacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;
    public function findById(string $id): mixed;
    public function create(array $data): mixed;
    public function update(string $id, array $data): mixed;
    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Visitas/Contracts/PlanVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface PlanVisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;
    public function findById(string $id): mixed;
    public function create(array $data): mixed;
    public function update(string $id, array $data): mixed;
    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Visitas/Contracts/VisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface VisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, array $filtros = []): array;
    public function findById(string $id): mixed;
    public function create(array $data): mixed;
    public function update(string $id, array $data): mixed;
    public function delete(string|array $ids): bool;

    /** Actualiza estado + campos propios de iniciar/finalizar/reprogramar. Usado por los Handlers de ciclo de vida. */
    public function actualizarEstado(string $id, array $data): mixed;

    /** Usado únicamente por ReasignarVisitaHandler (Etapa 6b), ver EloquentVisitaRepository::reasignarTecnico(). */
    public function reasignarTecnico(string $id, string $nuevoUserId): mixed;

    /** Usado únicamente por RevisarVisitaHandler (Etapa 6d). */
    public function actualizarEstadoRevision(string $id, string $estadoRevision): mixed;

    public function existeAsignacionActiva(string $visitaId): bool;
}
```

#### app/Domain/Visitas/Exceptions/MotivoVisitaNotFoundException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class MotivoVisitaNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Motivo de visita '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/Visitas/Exceptions/CategoriaObservacionNotFoundException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class CategoriaObservacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Categoría de observación '{$id}' no encontrada.", 404);
    }
}
```

#### app/Domain/Visitas/Exceptions/PlanVisitaNotFoundException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class PlanVisitaNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Plan de visitas '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/Visitas/Exceptions/VisitaNotFoundException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class VisitaNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Visita '{$id}' no encontrada.", 404);
    }
}
```

### Application

#### app/Application/Visitas/DTOs/MotivoVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class MotivoVisitaDTO
{
    public function __construct(
        public string $id,
        public string $motivoVisita,
        public ?string $descripcion,
        public bool $estado,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            motivoVisita: $model->motivo_visita,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Visitas/DTOs/CategoriaObservacionDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class CategoriaObservacionDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $categoriaObservaciones,
        public ?string $descripcion,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            categoriaObservaciones: $model->categoria_observaciones,
            descripcion: $model->descripcion,
            estado: (bool) $model->estado,
        );
    }
}
```

#### app/Application/Visitas/DTOs/PlanVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class PlanVisitaDTO
{
    public function __construct(
        public string $id,
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $estado,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            plan: $model->plan,
            anio: (int) $model->anio,
            fechaInicio: $model->fecha_inicio?->toDateString(),
            fechaFin: $model->fecha_fin?->toDateString(),
            estado: $model->estado,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Visitas/DTOs/VisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class VisitaDTO
{
    public function __construct(
        public string $id,
        public ?string $planVisitaId,
        public string $patrocinadoId,
        public string $userId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
        public ?string $fechaHabilitacion,
        public ?string $fechaInicio,
        public ?string $fechaFinalizacion,
        public string $estado,
        public string $estadoRevision,
        public string $estadoSincronizacion,
        public int $version,
        public int $intentosReprogramacion,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            planVisitaId: $model->plan_visita_id,
            patrocinadoId: $model->patrocinado_id,
            userId: $model->user_id,
            motivoVisitaId: $model->motivo_visita_id,
            fechaProgramada: $model->fecha_programada?->toDateString(),
            fechaHabilitacion: $model->fecha_habilitacion?->toIso8601String(),
            fechaInicio: $model->fecha_inicio?->toIso8601String(),
            fechaFinalizacion: $model->fecha_finalizacion?->toIso8601String(),
            estado: $model->estado,
            estadoRevision: $model->estado_revision,
            estadoSincronizacion: $model->estado_sincronizacion,
            version: (int) $model->version,
            intentosReprogramacion: (int) $model->intentos_reprogramacion,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Visitas/Commands/CreateMotivoVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateMotivoVisitaCommand
{
    public function __construct(
        public string $motivoVisita,
        public ?string $descripcion,
        public string $updatedBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/UpdateMotivoVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdateMotivoVisitaCommand
{
    public function __construct(
        public string $id,
        public string $motivoVisita,
        public ?string $descripcion,
        public bool $estado,
        public string $updatedBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/DeleteMotivoVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class DeleteMotivoVisitaCommand
{
    public function __construct(public string|array $ids) {}
}
```

> `CategoriaObservacion` y `PlanVisita` siguen exactamente el mismo trío Create/Update/Delete Command — se omiten por brevedad de repetición literal; únicamente cambian los campos: `CategoriaObservacion` agrega `codigo`, `PlanVisita` usa `plan`, `anio`, `fechaInicio`, `fechaFin`, `estado`, `createdBy` (solo en create).

#### app/Application/Visitas/Commands/CreatePlanVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class CreatePlanVisitaCommand
{
    public function __construct(
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $createdBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/UpdatePlanVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdatePlanVisitaCommand
{
    public function __construct(
        public string $id,
        public string $plan,
        public int $anio,
        public string $fechaInicio,
        public string $fechaFin,
        public string $estado,
        public string $updatedBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/CreateVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateVisitaCommand
{
    public function __construct(
        public ?string $planVisitaId,
        public string $patrocinadoId,
        public string $userId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
        public string $createdBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/UpdateVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class UpdateVisitaCommand
{
    public function __construct(
        public string $id,
        public ?string $planVisitaId,
        public ?string $motivoVisitaId,
        public ?string $fechaProgramada,
    ) {}
    // Intencionalmente sin patrocinado_id/user_id/estado: user_id solo cambia vía
    // ReasignarVisitaHandler (6b), estado solo vía iniciar/finalizar/reprogramar (abajo).
}
```

#### app/Application/Visitas/Commands/IniciarVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class IniciarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public string $ejecutadoPor,
    ) {}
}
```

#### app/Application/Visitas/Commands/FinalizarVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class FinalizarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $estadoFinal, // FINALIZADA | NO_ENCONTRADO | CANCELADA
        public string $ejecutadoPor,
    ) {}
}
```

#### app/Application/Visitas/Commands/ReprogramarVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class ReprogramarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $ejecutadoPor,
    ) {}
}
```

#### app/Application/Visitas/Commands/DarDeBajaPatrocinadoPorNoUbicadoCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class DarDeBajaPatrocinadoPorNoUbicadoCommand
{
    public function __construct(
        public string $patrocinadoId,
        public string $visitaId,
        public string $decididoPor,
        public ?string $comentario,
    ) {}
}
```

#### app/Application/Visitas/Handlers/CreateMotivoVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreateMotivoVisitaCommand;
use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class CreateMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(CreateMotivoVisitaCommand $command): MotivoVisitaDTO
    {
        $model = $this->repository->create([
            'motivo_visita' => $command->motivoVisita,
            'descripcion'   => $command->descripcion,
            'estado'        => true,
            'updated_by'    => $command->updatedBy,
        ]);

        return MotivoVisitaDTO::fromModel($model);
    }
}
```

#### app/Application/Visitas/Handlers/UpdateMotivoVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdateMotivoVisitaCommand;
use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class UpdateMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(UpdateMotivoVisitaCommand $command): MotivoVisitaDTO
    {
        $model = $this->repository->update($command->id, [
            'motivo_visita' => $command->motivoVisita,
            'descripcion'   => $command->descripcion,
            'estado'        => $command->estado,
            'updated_by'    => $command->updatedBy,
        ]);

        return MotivoVisitaDTO::fromModel($model);
    }
}
```

#### app/Application/Visitas/Handlers/DeleteMotivoVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DeleteMotivoVisitaCommand;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class DeleteMotivoVisitaHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(DeleteMotivoVisitaCommand $command): bool
    {
        return $this->repository->delete($command->ids);
    }
}
```

> `CategoriaObservacion` y `PlanVisita` tienen sus Create/Update/DeleteHandler análogos (mismo cuerpo, cambiando el Repository/Command inyectado): `CreateCategoriaObservacionHandler`, `UpdateCategoriaObservacionHandler`, `DeleteCategoriaObservacionHandler`, `CreatePlanVisitaHandler`, `UpdatePlanVisitaHandler`, `DeletePlanVisitaHandler` — se omiten por ser copias literales del patrón de arriba.

#### app/Application/Visitas/Handlers/CreateVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\CreateVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(CreateVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->create([
                'plan_visita_id'   => $command->planVisitaId,
                'patrocinado_id'   => $command->patrocinadoId,
                'user_id'          => $command->userId,
                'motivo_visita_id' => $command->motivoVisitaId,
                'fecha_programada' => $command->fechaProgramada,
                'estado'           => 'PLANIFICADA',
                'created_by'       => $command->createdBy,
            ]);

            $this->auditoria->registrar(
                userId: $command->createdBy,
                dispositivoId: null,
                accion: 'crear',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $model->id,
                valoresAnteriores: null,
                valoresNuevos: ['patrocinado_id' => $command->patrocinadoId, 'user_id' => $command->userId, 'estado' => 'PLANIFICADA'],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Handlers/UpdateVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdateVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;

class UpdateVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository
    ) {}

    public function handle(UpdateVisitaCommand $command): VisitaDTO
    {
        $model = $this->repository->update($command->id, [
            'plan_visita_id'   => $command->planVisitaId,
            'motivo_visita_id' => $command->motivoVisitaId,
            'fecha_programada' => $command->fechaProgramada,
        ]);

        return VisitaDTO::fromModel($model);
    }
}
```

#### app/Application/Visitas/Handlers/IniciarVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\IniciarVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class IniciarVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(IniciarVisitaCommand $command): VisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'       => 'EN_CURSO',
                'fecha_inicio' => now(),
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: $command->dispositivoId,
                accion: 'iniciar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => 'EN_CURSO'],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Handlers/FinalizarVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\FinalizarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

class FinalizarVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(FinalizarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'              => $command->estadoFinal,
                'fecha_finalizacion'  => now(),
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: null,
                accion: 'finalizar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado],
                valoresNuevos: ['estado' => $command->estadoFinal],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Handlers/ReprogramarVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\ReprogramarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Implementación mínima viable — la regla completa de reprogramación (docs/patrocinados/06-visitas.md,
 * "Decisiones de negocio a cerrar" #1) está pendiente de cierre con negocio:
 * cuántos ciclos exactos, quién decide la baja final. Este Handler cubre el camino feliz
 * (reprogramar a 3 meses) y deja el camino de "agotó reprogramaciones" delegado a un
 * Command separado (DarDeBajaPatrocinadoPorNoUbicadoCommand) en vez de decidirlo aquí mismo.
 */
class ReprogramarVisitaHandler
{
    private const MAX_REPROGRAMACIONES = 2; // pendiente de confirmación de negocio

    public function __construct(
        private readonly VisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(ReprogramarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $anterior = $this->repository->findById($command->visitaId);

            if ($anterior->intentos_reprogramacion >= self::MAX_REPROGRAMACIONES) {
                throw new \RuntimeException(
                    'Se agotaron los intentos de reprogramación. Debe decidirse la baja del patrocinado '
                    . 'vía DarDeBajaPatrocinadoPorNoUbicadoCommand (requiere decisión de un supervisor).',
                    422
                );
            }

            $model = $this->repository->actualizarEstado($command->visitaId, [
                'estado'                  => 'REPROGRAMADA',
                'fecha_programada'        => now()->addMonths(3)->toDateString(),
                'intentos_reprogramacion' => $anterior->intentos_reprogramacion + 1,
            ]);

            $this->auditoria->registrar(
                userId: $command->ejecutadoPor,
                dispositivoId: null,
                accion: 'reprogramar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['estado' => $anterior->estado, 'intentos_reprogramacion' => $anterior->intentos_reprogramacion],
                valoresNuevos: ['estado' => 'REPROGRAMADA', 'intentos_reprogramacion' => $anterior->intentos_reprogramacion + 1],
            );

            return VisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Handlers/DarDeBajaPatrocinadoPorNoUbicadoHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DarDeBajaPatrocinadoPorNoUbicadoCommand;

/**
 * TODO: pendiente de coordinar con Etapa 5 (Patrocinados). Debe:
 *  1. Inyectar PatrocinadoRepositoryInterface (Domain/Patrocinados).
 *  2. Actualizar estados_patrocinados del niño a 'INACTIVO_NO_UBICADO' (código exacto
 *     a confirmar en la Etapa 5 — ver docs/patrocinados/05-patrocinados.md, decisión #2).
 *  3. Registrar en AuditoriaService (módulo 'Patrocinados', accion 'dar-de-baja').
 *  4. Marcar la visita asociada como CANCELADA vía VisitaRepositoryInterface::actualizarEstado().
 * No implementar el cambio de estado del patrocinado en este módulo (Visitas) para no
 * duplicar la fuente de verdad de estados_patrocinados fuera de Application/Patrocinados.
 */
class DarDeBajaPatrocinadoPorNoUbicadoHandler
{
    public function handle(DarDeBajaPatrocinadoPorNoUbicadoCommand $command): void
    {
        throw new \RuntimeException(
            'DarDeBajaPatrocinadoPorNoUbicadoHandler pendiente de implementación — '
            . 'requiere cerrar la Etapa 5 y la regla de negocio de reprogramación.'
        );
    }
}
```

#### app/Application/Visitas/Queries/GetMotivosVisitasQuery.php
```php
<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetMotivosVisitasQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

> `GetCategoriasObservacionesQuery`, `GetPlanesVisitasQuery` son idénticas (mismo único campo `pagination`).

#### app/Application/Visitas/Queries/GetVisitasQuery.php
```php
<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetVisitasQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $patrocinadoId = null,
        public ?string $tecnicoId = null,
        public ?string $estado = null,
        public ?string $desde = null,
        public ?string $hasta = null,
    ) {}
}
```

#### app/Application/Visitas/Queries/GetVisitaByIdQuery.php
```php
<?php

namespace App\Application\Visitas\Queries;

final readonly class GetVisitaByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Visitas/QueryHandlers/GetMotivosVisitasQueryHandler.php
```php
<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetMotivosVisitasQuery;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;

class GetMotivosVisitasQueryHandler
{
    public function __construct(
        private readonly MotivoVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetMotivosVisitasQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

> `GetCategoriasObservacionesQueryHandler`, `GetPlanesVisitasQueryHandler` son idénticos, cambiando el repositorio inyectado.

#### app/Application/Visitas/QueryHandlers/GetVisitasQueryHandler.php
```php
<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetVisitasQuery;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;

class GetVisitasQueryHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository
    ) {}

    public function handle(GetVisitasQuery $query): array
    {
        return $this->repository->paginate($query->pagination, [
            'patrocinado_id' => $query->patrocinadoId,
            'tecnico_id'     => $query->tecnicoId,
            'estado'         => $query->estado,
            'desde'          => $query->desde,
            'hasta'          => $query->hasta,
        ]);
    }
}
```

#### app/Application/Visitas/QueryHandlers/GetVisitaByIdQueryHandler.php
```php
<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\DTOs\VisitaDTO;
use App\Application\Visitas\Queries\GetVisitaByIdQuery;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\VisitaNotFoundException;

class GetVisitaByIdQueryHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $repository
    ) {}

    public function handle(GetVisitaByIdQuery $query): VisitaDTO
    {
        $model = $this->repository->findById($query->id);
        if (! $model) {
            throw new VisitaNotFoundException($query->id);
        }

        // Carga asignación activa, habilitación activa, observaciones, fotos (metadatos)
        // y revisión vigente vía eager loading en el Repository (ver EloquentVisitaRepository::findById()).
        return VisitaDTO::fromModel($model);
    }
}
```

### Infrastructure

#### app/Infrastructure/Visitas/Models/MotivoVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MotivoVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'motivos_visitas';

    protected $fillable = ['motivo_visita', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}
```

#### app/Infrastructure/Visitas/Models/CategoriaObservacion.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CategoriaObservacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'categorias_observaciones';

    protected $fillable = ['codigo', 'categoria_observaciones', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}
```

#### app/Infrastructure/Visitas/Models/PlanVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'planes_visitas';

    protected $fillable = ['plan', 'anio', 'fecha_inicio', 'fecha_fin', 'estado', 'created_by', 'updated_by'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
}
```

#### app/Infrastructure/Visitas/Models/Visita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visita extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'visitas';

    protected $fillable = [
        'plan_visita_id', 'patrocinado_id', 'user_id', 'motivo_visita_id',
        'fecha_programada', 'fecha_habilitacion', 'fecha_inicio', 'fecha_finalizacion',
        'estado', 'estado_revision', 'estado_sincronizacion', 'version',
        'intentos_reprogramacion', 'created_by',
    ];

    protected $casts = [
        'fecha_programada'    => 'date',
        'fecha_habilitacion'  => 'datetime',
        'fecha_inicio'        => 'datetime',
        'fecha_finalizacion'  => 'datetime',
        'version'             => 'integer',
        'intentos_reprogramacion' => 'integer',
    ];

    public function asignacionActiva()
    {
        return $this->hasOne(AsignacionVisita::class, 'visita_id')->where('estado', true);
    }

    public function habilitacionActiva()
    {
        return $this->hasOne(HabilitacionVisita::class, 'visita_id')->where('estado', 'ACTIVA');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionVisita::class, 'visita_id');
    }

    public function fotos()
    {
        return $this->hasMany(FotoVisita::class, 'visita_id');
    }

    public function revisionVigente()
    {
        return $this->hasOne(RevisionVisita::class, 'visita_id')->latestOfMany('fecha_revision');
    }
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentMotivoVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\MotivoVisitaDTO;
use App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\MotivoVisitaNotFoundException;
use App\Infrastructure\Visitas\Models\MotivoVisita;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentMotivoVisitaRepository implements MotivoVisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = MotivoVisita::query();

        if ($pagination->query) {
            $q->where('motivo_visita', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?? 'motivo_visita', $pagination->sortOrder ?? 'asc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($m) => MotivoVisitaDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return MotivoVisita::find($id);
    }

    public function create(array $data): mixed
    {
        return MotivoVisita::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = MotivoVisita::find($id);
        if (! $model) {
            throw new MotivoVisitaNotFoundException($id);
        }
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) MotivoVisita::destroy($ids);
    }
}
```

> `EloquentCategoriaObservacionRepository` y `EloquentPlanVisitaRepository` siguen el mismo esqueleto (búsqueda por su propio campo de texto principal, resto idéntico) — se omiten por ser copias literales.

#### app/Infrastructure/Visitas/Repositories/EloquentVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\VisitaNotFoundException;
use App\Infrastructure\Visitas\Models\Visita;
use App\Shared\Kernel\DTOs\PaginationDTO;

/**
 * Único Repository de todo el módulo Visitas con métodos de escritura no genéricos
 * (reasignarTecnico, actualizarEstado, actualizarEstadoRevision) — deliberado: son
 * los únicos caminos válidos para tocar user_id / estado / estado_revision, según
 * las reglas de sincronía documentadas en docs/patrocinados/06-visitas.md.
 */
class EloquentVisitaRepository implements VisitaRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, array $filtros = []): array
    {
        $q = Visita::query()->with(['asignacionActiva', 'habilitacionActiva']);

        if (! empty($filtros['patrocinado_id'])) {
            $q->where('patrocinado_id', $filtros['patrocinado_id']);
        }
        if (! empty($filtros['tecnico_id'])) {
            $q->where('user_id', $filtros['tecnico_id']);
        }
        if (! empty($filtros['estado'])) {
            $q->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['desde'])) {
            $q->whereDate('fecha_programada', '>=', $filtros['desde']);
        }
        if (! empty($filtros['hasta'])) {
            $q->whereDate('fecha_programada', '<=', $filtros['hasta']);
        }

        $paginated = $q->orderBy('fecha_programada', 'desc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($v) => \App\Application\Visitas\DTOs\VisitaDTO::fromModel($v))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return Visita::with(['asignacionActiva', 'habilitacionActiva', 'observaciones', 'fotos', 'revisionVigente'])->find($id);
    }

    public function create(array $data): mixed
    {
        return Visita::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update($data);
        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Visita::destroy($ids);
    }

    public function actualizarEstado(string $id, array $data): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update($data);
        return $model->refresh();
    }

    public function reasignarTecnico(string $id, string $nuevoUserId): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update(['user_id' => $nuevoUserId]);
        return $model->refresh();
    }

    public function actualizarEstadoRevision(string $id, string $estadoRevision): mixed
    {
        $model = $this->obtenerOFallar($id);
        $model->update(['estado_revision' => $estadoRevision]);
        return $model->refresh();
    }

    public function existeAsignacionActiva(string $visitaId): bool
    {
        return \App\Infrastructure\Visitas\Models\AsignacionVisita::where('visita_id', $visitaId)
            ->where('estado', true)
            ->exists();
    }

    private function obtenerOFallar(string $id): Visita
    {
        $model = Visita::find($id);
        if (! $model) {
            throw new VisitaNotFoundException($id);
        }
        return $model;
    }
}
```

### Http

#### app/Http/Controllers/Api/Patrocinados/MotivoVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateMotivoVisitaCommand;
use App\Application\Visitas\Commands\DeleteMotivoVisitaCommand;
use App\Application\Visitas\Commands\UpdateMotivoVisitaCommand;
use App\Application\Visitas\Handlers\CreateMotivoVisitaHandler;
use App\Application\Visitas\Handlers\DeleteMotivoVisitaHandler;
use App\Application\Visitas\Handlers\UpdateMotivoVisitaHandler;
use App\Application\Visitas\Queries\GetMotivosVisitasQuery;
use App\Application\Visitas\QueryHandlers\GetMotivosVisitasQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreMotivoVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdateMotivoVisitaRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotivoVisitaController extends Controller
{
    public function __construct(
        private readonly GetMotivosVisitasQueryHandler $getHandler,
        private readonly CreateMotivoVisitaHandler $createHandler,
        private readonly UpdateMotivoVisitaHandler $updateHandler,
        private readonly DeleteMotivoVisitaHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getHandler->handle(new GetMotivosVisitasQuery($pagination)));
    }

    public function store(StoreMotivoVisitaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateMotivoVisitaCommand(
            motivoVisita: $request->motivo_visita,
            descripcion:  $request->descripcion,
            updatedBy:    auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateMotivoVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateMotivoVisitaCommand(
            id:           $id,
            motivoVisita: $request->motivo_visita,
            descripcion:  $request->descripcion,
            estado:       $request->boolean('estado', true),
            updatedBy:    auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteMotivoVisitaCommand($id));
        return response()->json(null, 204);
    }
}
```

> `CategoriaObservacionController` sigue el mismo patrón (campos `codigo` + `categoria_observaciones`). `PlanVisitaController` también, agregando `anio`/`fecha_inicio`/`fecha_fin`/`estado` y pasando `createdBy: auth()->id()` solo en `store()`.

#### app/Http/Controllers/Api/Patrocinados/VisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateVisitaCommand;
use App\Application\Visitas\Commands\FinalizarVisitaCommand;
use App\Application\Visitas\Commands\IniciarVisitaCommand;
use App\Application\Visitas\Commands\ReprogramarVisitaCommand;
use App\Application\Visitas\Commands\UpdateVisitaCommand;
use App\Application\Visitas\Handlers\CreateVisitaHandler;
use App\Application\Visitas\Handlers\FinalizarVisitaHandler;
use App\Application\Visitas\Handlers\IniciarVisitaHandler;
use App\Application\Visitas\Handlers\ReprogramarVisitaHandler;
use App\Application\Visitas\Handlers\UpdateVisitaHandler;
use App\Application\Visitas\Queries\GetVisitaByIdQuery;
use App\Application\Visitas\Queries\GetVisitasQuery;
use App\Application\Visitas\QueryHandlers\GetVisitaByIdQueryHandler;
use App\Application\Visitas\QueryHandlers\GetVisitasQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\FinalizarVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\IniciarVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\StoreVisitaRequest;
use App\Http\Requests\Patrocinados\Visitas\UpdateVisitaRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitaController extends Controller
{
    public function __construct(
        private readonly GetVisitasQueryHandler $getVisitasHandler,
        private readonly GetVisitaByIdQueryHandler $getVisitaByIdHandler,
        private readonly CreateVisitaHandler $createHandler,
        private readonly UpdateVisitaHandler $updateHandler,
        private readonly IniciarVisitaHandler $iniciarHandler,
        private readonly FinalizarVisitaHandler $finalizarHandler,
        private readonly ReprogramarVisitaHandler $reprogramarHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray([
            'pageIndex' => $request->get('pageIndex', 1),
            'pageSize'  => $request->get('pageSize', 15),
            'query'     => $request->get('query', ''),
        ]);

        return response()->json($this->getVisitasHandler->handle(new GetVisitasQuery(
            pagination: $pagination,
            patrocinadoId: $request->get('patrocinado_id'),
            tecnicoId: $request->get('tecnico_id'),
            estado: $request->get('estado'),
            desde: $request->get('desde'),
            hasta: $request->get('hasta'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getVisitaByIdHandler->handle(new GetVisitaByIdQuery($id)));
    }

    public function store(StoreVisitaRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateVisitaCommand(
            planVisitaId: $request->plan_visita_id,
            patrocinadoId: $request->patrocinado_id,
            userId: $request->user_id,
            motivoVisitaId: $request->motivo_visita_id,
            fechaProgramada: $request->fecha_programada,
            createdBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateVisitaCommand(
            id: $id,
            planVisitaId: $request->plan_visita_id,
            motivoVisitaId: $request->motivo_visita_id,
            fechaProgramada: $request->fecha_programada,
        ));

        return response()->json($dto);
    }

    public function iniciar(IniciarVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->iniciarHandler->handle(new IniciarVisitaCommand(
            visitaId: $id,
            dispositivoId: $request->dispositivo_id,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function finalizar(FinalizarVisitaRequest $request, string $id): JsonResponse
    {
        $dto = $this->finalizarHandler->handle(new FinalizarVisitaCommand(
            visitaId: $id,
            estadoFinal: $request->estado_final,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function reprogramar(string $id): JsonResponse
    {
        $dto = $this->reprogramarHandler->handle(new ReprogramarVisitaCommand(
            visitaId: $id,
            ejecutadoPor: auth()->id(),
        ));

        return response()->json($dto);
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/StoreMotivoVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class StoreMotivoVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_visita' => ['required', 'string', 'max:120'],
            'descripcion'   => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/UpdateMotivoVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMotivoVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_visita' => ['required', 'string', 'max:120'],
            'descripcion'   => ['nullable', 'string', 'max:255'],
            'estado'        => ['sometimes', 'boolean'],
        ];
    }
}
```

> `StoreCategoriaObservacionRequest`/`UpdateCategoriaObservacionRequest`, `StorePlanVisitaRequest`/`UpdatePlanVisitaRequest` siguen el mismo esqueleto validando sus propios campos (`codigo`+`categoria_observaciones`; `plan`+`anio`+`fecha_inicio`+`fecha_fin`+`estado:in:ACTIVO,CERRADO,CANCELADO`).

#### app/Http/Requests/Patrocinados/Visitas/StoreVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class StoreVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_visita_id'   => ['nullable', 'uuid', 'exists:pgsql_patrocinados.planes_visitas,id'],
            'patrocinado_id'   => ['required', 'uuid', 'exists:pgsql_patrocinados.patrocinados,id'],
            'user_id'          => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
            'motivo_visita_id' => ['nullable', 'uuid', 'exists:pgsql_patrocinados.motivos_visitas,id'],
            'fecha_programada' => ['nullable', 'date'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/UpdateVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_visita_id'   => ['nullable', 'uuid', 'exists:pgsql_patrocinados.planes_visitas,id'],
            'motivo_visita_id' => ['nullable', 'uuid', 'exists:pgsql_patrocinados.motivos_visitas,id'],
            'fecha_programada' => ['nullable', 'date'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/IniciarVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class IniciarVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/FinalizarVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinalizarVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado_final' => ['required', Rule::in(['FINALIZADA', 'NO_ENCONTRADO', 'CANCELADA'])],
        ];
    }
}
```

---

## 6b — Asignación y habilitación (`asignaciones_visitas`, `habilitaciones_visitas`)

### Domain

#### app/Domain/Visitas/Contracts/AsignacionVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

interface AsignacionVisitaRepositoryInterface
{
    public function findActivaPorVisita(string $visitaId): mixed;
    public function cerrarActiva(string $visitaId): void;
    public function create(array $data): mixed;
}
```

#### app/Domain/Visitas/Contracts/HabilitacionVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

interface HabilitacionVisitaRepositoryInterface
{
    public function findById(string $id): mixed;
    public function findActiva(string $visitaId, string $dispositivoId): mixed;
    public function create(array $data): mixed;
    public function revocar(string $id, string $revokedBy): mixed;
}
```

#### app/Domain/Visitas/Exceptions/VisitaYaAsignadaException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class VisitaYaAsignadaException extends \RuntimeException
{
    public function __construct(string $visitaId)
    {
        parent::__construct("La visita '{$visitaId}' ya tiene una asignación activa.", 422);
    }
}
```

#### app/Domain/Visitas/Exceptions/DispositivoNoHabilitadoException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class DispositivoNoHabilitadoException extends \RuntimeException
{
    public function __construct(string $visitaId, string $dispositivoId)
    {
        parent::__construct(
            "El dispositivo '{$dispositivoId}' no tiene una habilitación activa para la visita '{$visitaId}'.",
            403
        );
    }
}
```

#### app/Domain/Visitas/Exceptions/HabilitacionExpiradaException.php
```php
<?php

namespace App\Domain\Visitas\Exceptions;

class HabilitacionExpiradaException extends \RuntimeException
{
    public function __construct(string $habilitacionId)
    {
        parent::__construct("La habilitación '{$habilitacionId}' expiró.", 403);
    }
}
```

### Application

#### app/Application/Visitas/DTOs/AsignacionVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class AsignacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $tecnicoId,
        public string $assignedBy,
        public string $fechaAsignacion,
        public ?string $fechaDesasignacion,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            tecnicoId: $model->tecnico_id,
            assignedBy: $model->assigned_by,
            fechaAsignacion: $model->fecha_asignacion?->toIso8601String(),
            fechaDesasignacion: $model->fecha_desasignacion?->toIso8601String(),
            estado: (bool) $model->estado,
        );
    }
}
```

#### app/Application/Visitas/DTOs/HabilitacionVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class HabilitacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $tecnicoId,
        public string $dispositivoId,
        public string $authorizedBy,
        public string $fechaHabilitacion,
        public string $fechaExpiracion,
        public string $estado,
        public ?string $fechaRevocacion,
        public ?string $revokedBy,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            tecnicoId: $model->tecnico_id,
            dispositivoId: $model->dispositivo_id,
            authorizedBy: $model->authorized_by,
            fechaHabilitacion: $model->fecha_habilitacion?->toIso8601String(),
            fechaExpiracion: $model->fecha_expiracion?->toIso8601String(),
            estado: $model->estado,
            fechaRevocacion: $model->fecha_revocacion?->toIso8601String(),
            revokedBy: $model->revoked_by,
        );
    }
}
```

#### app/Application/Visitas/Commands/ReasignarVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class ReasignarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $nuevoTecnicoId,
        public string $assignedBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/HabilitarDispositivoParaVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class HabilitarDispositivoParaVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $tecnicoId,
        public string $dispositivoId,
        public string $authorizedBy,
        public \DateTimeInterface $fechaExpiracion,
    ) {}
}
```

#### app/Application/Visitas/Commands/RevocarHabilitacionVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class RevocarHabilitacionVisitaCommand
{
    public function __construct(
        public string $habilitacionId,
        public string $revokedBy,
    ) {}
}
```

#### app/Application/Visitas/Handlers/ReasignarVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\ReasignarVisitaCommand;
use App\Application\Visitas\DTOs\VisitaDTO;
use App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\VisitaYaAsignadaException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ReasignarVisitaHandler
{
    public function __construct(
        private readonly VisitaRepositoryInterface $visitaRepository,
        private readonly AsignacionVisitaRepositoryInterface $asignacionRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(ReasignarVisitaCommand $command): VisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $visitaAnterior = $this->visitaRepository->findById($command->visitaId);

            // Chequeo explícito antes del insert: evita depender de capturar la
            // violación del índice único parcial como único mecanismo de control.
            if ($this->visitaRepository->existeAsignacionActiva($command->visitaId)) {
                $this->asignacionRepository->cerrarActiva($command->visitaId);
            }

            try {
                $this->asignacionRepository->create([
                    'visita_id'         => $command->visitaId,
                    'tecnico_id'        => $command->nuevoTecnicoId,
                    'assigned_by'       => $command->assignedBy,
                    'fecha_asignacion'  => now(),
                    'estado'            => true,
                ]);
            } catch (QueryException $e) {
                if (str_contains($e->getMessage(), 'uq_asignaciones_visitas_activa')) {
                    throw new VisitaYaAsignadaException($command->visitaId);
                }
                throw $e;
            }

            $modelVisita = $this->visitaRepository->reasignarTecnico($command->visitaId, $command->nuevoTecnicoId);

            $this->auditoria->registrar(
                userId: $command->assignedBy,
                dispositivoId: null,
                accion: 'reasignar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: ['user_id' => $visitaAnterior->user_id],
                valoresNuevos: ['user_id' => $command->nuevoTecnicoId],
            );

            return VisitaDTO::fromModel($modelVisita);
        });
    }
}
```

#### app/Application/Visitas/Handlers/HabilitarDispositivoParaVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\HabilitarDispositivoParaVisitaCommand;
use App\Application\Visitas\DTOs\HabilitacionVisitaDTO;
use App\Domain\Dispositivos\Contracts\DispositivoRepositoryInterface;
use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\DispositivoNoHabilitadoException;
use Illuminate\Support\Facades\DB;

class HabilitarDispositivoParaVisitaHandler
{
    public function __construct(
        private readonly HabilitacionVisitaRepositoryInterface $repository,
        private readonly DispositivoRepositoryInterface $dispositivoRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(HabilitarDispositivoParaVisitaCommand $command): HabilitacionVisitaDTO
    {
        $dispositivo = $this->dispositivoRepository->findById($command->dispositivoId);
        if (! $dispositivo || $dispositivo->estado === 'REVOCADO') {
            // NUNCA se toca dispositivos.Estado desde este Handler — solo se lee para validar.
            throw new DispositivoNoHabilitadoException('(pendiente)', $command->dispositivoId);
        }

        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->create([
                'visita_id'         => $command->visitaId,
                'tecnico_id'        => $command->tecnicoId,
                'dispositivo_id'    => $command->dispositivoId,
                'authorized_by'     => $command->authorizedBy,
                'fecha_habilitacion'=> now(),
                'fecha_expiracion'  => $command->fechaExpiracion,
                'estado'            => 'ACTIVA',
            ]);

            $this->auditoria->registrar(
                userId: $command->authorizedBy,
                dispositivoId: $command->dispositivoId,
                accion: 'habilitar-dispositivo',
                modulo: 'Visitas',
                tipoEntidad: 'habilitacion_visita',
                entidadId: $model->id,
                valoresAnteriores: null,
                valoresNuevos: ['visita_id' => $command->visitaId, 'dispositivo_id' => $command->dispositivoId, 'estado' => 'ACTIVA'],
            );

            return HabilitacionVisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Handlers/RevocarHabilitacionVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\RevocarHabilitacionVisitaCommand;
use App\Application\Visitas\DTOs\HabilitacionVisitaDTO;
use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * NO toca dispositivos.Estado (regla de sincronía documentada en 06-visitas.md #5):
 * revocar una habilitación puntual de visita es independiente del estado general
 * del dispositivo, que solo cambia vía Etapa 4 (RevocarDispositivoHandler).
 */
class RevocarHabilitacionVisitaHandler
{
    public function __construct(
        private readonly HabilitacionVisitaRepositoryInterface $repository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevocarHabilitacionVisitaCommand $command): HabilitacionVisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->repository->revocar($command->habilitacionId, $command->revokedBy);

            $this->auditoria->registrar(
                userId: $command->revokedBy,
                dispositivoId: $model->dispositivo_id,
                accion: 'revocar-habilitacion',
                modulo: 'Visitas',
                tipoEntidad: 'habilitacion_visita',
                entidadId: $command->habilitacionId,
                valoresAnteriores: ['estado' => 'ACTIVA'],
                valoresNuevos: ['estado' => 'REVOCADA'],
            );

            return HabilitacionVisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Concerns/VerificaHabilitacionActiva.php
```php
<?php

namespace App\Application\Visitas\Concerns;

use App\Domain\Visitas\Exceptions\DispositivoNoHabilitadoException;
use App\Domain\Visitas\Exceptions\HabilitacionExpiradaException;
use App\Infrastructure\Visitas\Models\HabilitacionVisita;

/**
 * Usado por IniciarVisitaHandler y por los 3 Handlers de evidencia de campo (6c):
 * CapturarUbicacionVisitaHandler, CreateObservacionVisitaHandler, SubirFotoVisitaHandler.
 * Centraliza la regla de seguridad "todo dato de campo requiere habilitación ACTIVA
 * y no expirada" para no duplicar la query 4 veces.
 */
trait VerificaHabilitacionActiva
{
    protected function verificarHabilitacionActiva(string $visitaId, string $dispositivoId): HabilitacionVisita
    {
        $habilitacion = HabilitacionVisita::where('visita_id', $visitaId)
            ->where('dispositivo_id', $dispositivoId)
            ->where('estado', 'ACTIVA')
            ->first();

        if (! $habilitacion) {
            throw new DispositivoNoHabilitadoException($visitaId, $dispositivoId);
        }

        if ($habilitacion->fecha_expiracion->isPast()) {
            throw new HabilitacionExpiradaException($habilitacion->id);
        }

        return $habilitacion;
    }
}
```

### Infrastructure

#### app/Infrastructure/Visitas/Models/AsignacionVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AsignacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'asignaciones_visitas';

    protected $fillable = ['visita_id', 'tecnico_id', 'assigned_by', 'fecha_asignacion', 'fecha_desasignacion', 'estado'];

    protected $casts = [
        'fecha_asignacion'    => 'datetime',
        'fecha_desasignacion' => 'datetime',
        'estado'              => 'boolean',
    ];
}
```

#### app/Infrastructure/Visitas/Models/HabilitacionVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HabilitacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'habilitaciones_visitas';

    protected $fillable = [
        'visita_id', 'tecnico_id', 'dispositivo_id', 'authorized_by',
        'fecha_habilitacion', 'fecha_expiracion', 'estado', 'fecha_revocacion', 'revoked_by',
    ];

    protected $casts = [
        'fecha_habilitacion' => 'datetime',
        'fecha_expiracion'   => 'datetime',
        'fecha_revocacion'   => 'datetime',
    ];
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentAsignacionVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\AsignacionVisita;

class EloquentAsignacionVisitaRepository implements AsignacionVisitaRepositoryInterface
{
    public function findActivaPorVisita(string $visitaId): mixed
    {
        return AsignacionVisita::where('visita_id', $visitaId)->where('estado', true)->first();
    }

    public function cerrarActiva(string $visitaId): void
    {
        AsignacionVisita::where('visita_id', $visitaId)
            ->where('estado', true)
            ->update(['estado' => false, 'fecha_desasignacion' => now()]);
    }

    public function create(array $data): mixed
    {
        return AsignacionVisita::create($data);
    }
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentHabilitacionVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface;
use App\Domain\Visitas\Exceptions\HabilitacionExpiradaException;
use App\Infrastructure\Visitas\Models\HabilitacionVisita;

class EloquentHabilitacionVisitaRepository implements HabilitacionVisitaRepositoryInterface
{
    public function findById(string $id): mixed
    {
        return HabilitacionVisita::find($id);
    }

    public function findActiva(string $visitaId, string $dispositivoId): mixed
    {
        return HabilitacionVisita::where('visita_id', $visitaId)
            ->where('dispositivo_id', $dispositivoId)
            ->where('estado', 'ACTIVA')
            ->first();
    }

    public function create(array $data): mixed
    {
        return HabilitacionVisita::create($data);
    }

    public function revocar(string $id, string $revokedBy): mixed
    {
        $model = HabilitacionVisita::find($id);
        if (! $model) {
            throw new HabilitacionExpiradaException($id);
        }

        $model->update([
            'estado'           => 'REVOCADA',
            'fecha_revocacion' => now(),
            'revoked_by'       => $revokedBy,
        ]);

        return $model->refresh();
    }
}
```

### Http

#### app/Http/Controllers/Api/Patrocinados/HabilitacionVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\HabilitarDispositivoParaVisitaCommand;
use App\Application\Visitas\Commands\RevocarHabilitacionVisitaCommand;
use App\Application\Visitas\Handlers\HabilitarDispositivoParaVisitaHandler;
use App\Application\Visitas\Handlers\RevocarHabilitacionVisitaHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\HabilitarDispositivoRequest;
use Illuminate\Http\JsonResponse;

class HabilitacionVisitaController extends Controller
{
    public function __construct(
        private readonly HabilitarDispositivoParaVisitaHandler $habilitarHandler,
        private readonly RevocarHabilitacionVisitaHandler $revocarHandler,
    ) {}

    public function store(HabilitarDispositivoRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->habilitarHandler->handle(new HabilitarDispositivoParaVisitaCommand(
            visitaId: $visitaId,
            tecnicoId: $request->tecnico_id,
            dispositivoId: $request->dispositivo_id,
            authorizedBy: auth()->id(),
            fechaExpiracion: new \DateTimeImmutable($request->fecha_expiracion),
        ));

        return response()->json($dto, 201);
    }

    public function revocar(string $id): JsonResponse
    {
        $dto = $this->revocarHandler->handle(new RevocarHabilitacionVisitaCommand(
            habilitacionId: $id,
            revokedBy: auth()->id(),
        ));

        return response()->json($dto);
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/ReasignarVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class ReasignarVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nuevo_tecnico_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
        ];
    }
}
```

> `VisitaController::reasignar()` (endpoint `POST /visitas/{id}/reasignar`, no repetido aquí) inyecta `ReasignarVisitaHandler` igual que los métodos de 6a, usando este Request.

#### app/Http/Requests/Patrocinados/Visitas/HabilitarDispositivoRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class HabilitarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tecnico_id'       => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
            'dispositivo_id'   => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'fecha_expiracion' => ['required', 'date', 'after:now'],
        ];
    }
}
```

---

## 6c — Evidencia de campo (`ubicaciones_visitas`, `observaciones_visitas`, `fotos_visitas`)

### Domain

#### app/Domain/Visitas/Contracts/UbicacionVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

interface UbicacionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
}
```

#### app/Domain/Visitas/Contracts/ObservacionVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

interface ObservacionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
}
```

#### app/Domain/Visitas/Contracts/FotoVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

interface FotoVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function findById(string $id): mixed;
    public function listarPorVisita(string $visitaId): array;
}
```

### Application

#### app/Application/Visitas/DTOs/UbicacionVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class UbicacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $dispositivoId,
        public float $latitude,
        public float $longitude,
        public ?float $precisionMetros,
        public string $fuente,
        public string $fechaCaptura,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            dispositivoId: $model->dispositivo_id,
            latitude: (float) $model->latitude,
            longitude: (float) $model->longitude,
            precisionMetros: $model->precision_metros !== null ? (float) $model->precision_metros : null,
            fuente: $model->fuente,
            fechaCaptura: $model->fecha_captura?->toIso8601String(),
        );
    }
}
```

#### app/Application/Visitas/DTOs/ObservacionVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class ObservacionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public ?string $categoriaId,
        public string $tipo,
        public string $observacion,
        public string $createdBy,
        public ?string $createdAt,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            categoriaId: $model->categoria_id,
            tipo: $model->tipo,
            observacion: $model->observacion,
            createdBy: $model->created_by,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Visitas/DTOs/FotoVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

/** Nunca expone clave_almacenamiento cruda — solo urlFirmada, generada por el Controller/Service. */
final readonly class FotoVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public ?string $nombreArchivo,
        public string $tipoArchivo,
        public int $tamanio,
        public string $hashSha256,
        public ?string $fechaCaptura,
        public bool $cifrada,
        public ?string $urlFirmada = null,
    ) {}

    public static function fromModel(object $model, ?string $urlFirmada = null): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            nombreArchivo: $model->nombre_archivo,
            tipoArchivo: $model->tipo_archivo,
            tamanio: (int) $model->tamanio,
            hashSha256: $model->hash_sha256,
            fechaCaptura: $model->fecha_captura?->toIso8601String(),
            cifrada: (bool) $model->cifrada,
            urlFirmada: $urlFirmada,
        );
    }
}
```

#### app/Application/Visitas/Commands/CapturarUbicacionVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class CapturarUbicacionVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public string $tecnicoId,
        public float $latitude,
        public float $longitude,
        public ?float $precisionMetros,
        public string $fuente, // GPS | RED | MANUAL
    ) {}
}
```

#### app/Application/Visitas/Commands/CreateObservacionVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class CreateObservacionVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public ?string $categoriaId,
        public string $tipo,
        public string $observacion,
        public string $createdBy,
    ) {}
}
```

#### app/Application/Visitas/Commands/SubirFotoVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

use Illuminate\Http\UploadedFile;

final readonly class SubirFotoVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public UploadedFile $archivo,
        public ?float $latitude,
        public ?float $longitude,
    ) {}
}
```

#### app/Application/Visitas/Handlers/CapturarUbicacionVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CapturarUbicacionVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\UbicacionVisitaDTO;
use App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface;

/**
 * El Repository (EloquentUbicacionVisitaRepository) es la única fuente de verdad
 * de la derivación lat/lng -> GEOGRAPHY, igual mecanismo que Geografia (Etapa 3, Opción A).
 */
class CapturarUbicacionVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly UbicacionVisitaRepositoryInterface $repository
    ) {}

    public function handle(CapturarUbicacionVisitaCommand $command): UbicacionVisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $model = $this->repository->create([
            'visita_id'         => $command->visitaId,
            'dispositivo_id'    => $command->dispositivoId,
            'tecnico_id'        => $command->tecnicoId,
            'fecha_captura'     => now(),
            'latitude'          => $command->latitude,
            'longitude'         => $command->longitude,
            'precision_metros'  => $command->precisionMetros,
            'fuente'            => $command->fuente,
        ]);

        return UbicacionVisitaDTO::fromModel($model);
    }
}
```

#### app/Application/Visitas/Handlers/CreateObservacionVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreateObservacionVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\ObservacionVisitaDTO;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;

class CreateObservacionVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly ObservacionVisitaRepositoryInterface $repository
    ) {}

    public function handle(CreateObservacionVisitaCommand $command): ObservacionVisitaDTO
    {
        $habilitacion = $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $model = $this->repository->create([
            'visita_id'   => $command->visitaId,
            'categoria_id'=> $command->categoriaId,
            'tipo'        => $command->tipo,
            'observacion' => $command->observacion,
            'created_by'  => $habilitacion->tecnico_id,
        ]);

        return ObservacionVisitaDTO::fromModel($model);
    }
}
```

#### app/Application/Visitas/Handlers/SubirFotoVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\SubirFotoVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\FotoVisitaDTO;
use App\Application\Visitas\Services\FotoVisitaService;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;

class SubirFotoVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly FotoVisitaRepositoryInterface $repository,
        private readonly FotoVisitaService $fotoService,
    ) {}

    public function handle(SubirFotoVisitaCommand $command): FotoVisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $almacenado = $this->fotoService->almacenar($command->archivo);

        $model = $this->repository->create([
            'visita_id'            => $command->visitaId,
            'clave_almacenamiento' => $almacenado->clave,
            'nombre_archivo'       => $command->archivo->getClientOriginalName(),
            'tipo_archivo'         => $command->archivo->getMimeType(),
            'tamanio'              => $command->archivo->getSize(),
            'hash_sha256'          => $almacenado->hashSha256,
            'fecha_captura'        => now(),
            'latitude'             => $command->latitude,
            'longitude'            => $command->longitude,
            'cifrada'              => $almacenado->cifrada,
        ]);

        return FotoVisitaDTO::fromModel($model, $this->fotoService->urlFirmada($model->clave_almacenamiento));
    }
}
```

#### app/Application/Visitas/Services/FotoVisitaService.php
```php
<?php

namespace App\Application\Visitas\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mecanismo de cifrado pendiente de decisión de negocio (docs/patrocinados/06-visitas.md
 * §"Decisiones de negocio", plan de revisión §5.7): at-rest del disco/bucket vs cifrado
 * aplicativo por archivo. Este Service asume que el disco 'patrocinados-privado' ya
 * viene cifrado at-rest (S3 SSE / disco cifrado) y NO cifra el binario en PHP —
 * si negocio exige cifrado aplicativo, es el único punto a modificar (almacenar()).
 */
class FotoVisitaService
{
    private const DISCO = 'patrocinados-privado';

    public function almacenar(UploadedFile $archivo): object
    {
        $hash = hash_file('sha256', $archivo->getRealPath());
        $clave = 'visitas/fotos/' . Str::uuid() . '.' . $archivo->getClientOriginalExtension();

        Storage::disk(self::DISCO)->putFileAs('', $archivo, $clave);

        return (object) [
            'clave'      => $clave,
            'hashSha256' => $hash,
            'cifrada'    => true, // at-rest del disco, ver nota de clase
        ];
    }

    public function urlFirmada(string $clave, int $minutos = 15): ?string
    {
        $disco = Storage::disk(self::DISCO);

        if (method_exists($disco, 'temporaryUrl')) {
            return $disco->temporaryUrl($clave, now()->addMinutes($minutos));
        }

        // TODO: endpoint de streaming autenticado si el driver de storage no soporta
        // temporaryUrl (p.ej. disco 'local' en desarrollo) — no exponer URL pública permanente.
        return null;
    }
}
```

### Infrastructure

#### app/Infrastructure/Visitas/Models/UbicacionVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UbicacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'ubicaciones_visitas';

    protected $fillable = ['visita_id', 'dispositivo_id', 'tecnico_id', 'fecha_captura', 'latitude', 'longitude', 'precision_metros', 'fuente'];

    protected $casts = ['fecha_captura' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
}
```

#### app/Infrastructure/Visitas/Models/ObservacionVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ObservacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'observaciones_visitas';

    protected $fillable = ['visita_id', 'categoria_id', 'tipo', 'observacion', 'created_by'];
}
```

#### app/Infrastructure/Visitas/Models/FotoVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FotoVisita extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'fotos_visitas';

    protected $fillable = [
        'visita_id', 'clave_almacenamiento', 'nombre_archivo', 'tipo_archivo', 'tamanio',
        'ancho', 'alto', 'hash_sha256', 'fecha_captura', 'latitude', 'longitude', 'cifrada',
    ];

    protected $hidden = ['clave_almacenamiento'];

    protected $casts = ['fecha_captura' => 'datetime', 'cifrada' => 'boolean'];
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentUbicacionVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\UbicacionVisitaDTO;
use App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\UbicacionVisita;
use Illuminate\Support\Facades\DB;

/** Única fuente de verdad de la derivación lat/lng -> punto_geografico (regla §5.6). */
class EloquentUbicacionVisitaRepository implements UbicacionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        $model = UbicacionVisita::create($data);

        DB::connection('pgsql_patrocinados')->statement(
            'UPDATE ubicaciones_visitas SET punto_geografico = ST_MakePoint(?, ?)::geography WHERE id = ?',
            [$data['longitude'], $data['latitude'], $model->id]
        );

        return $model->refresh();
    }

    public function listarPorVisita(string $visitaId): array
    {
        return UbicacionVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_captura', 'desc')
            ->get()
            ->map(fn ($m) => UbicacionVisitaDTO::fromModel($m))
            ->all();
    }
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentObservacionVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\ObservacionVisitaDTO;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\ObservacionVisita;

class EloquentObservacionVisitaRepository implements ObservacionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return ObservacionVisita::create($data);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return ObservacionVisita::where('visita_id', $visitaId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($m) => ObservacionVisitaDTO::fromModel($m))
            ->all();
    }
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentFotoVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\FotoVisitaDTO;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\FotoVisita;

class EloquentFotoVisitaRepository implements FotoVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return FotoVisita::create($data);
    }

    public function findById(string $id): mixed
    {
        return FotoVisita::find($id);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return FotoVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_captura', 'desc')
            ->get()
            ->map(fn ($m) => FotoVisitaDTO::fromModel($m))
            ->all();
    }
}
```

### Http

#### app/Http/Controllers/Api/Patrocinados/UbicacionVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CapturarUbicacionVisitaCommand;
use App\Application\Visitas\Handlers\CapturarUbicacionVisitaHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\CapturarUbicacionVisitaRequest;
use Illuminate\Http\JsonResponse;

class UbicacionVisitaController extends Controller
{
    public function __construct(
        private readonly CapturarUbicacionVisitaHandler $handler
    ) {}

    public function store(CapturarUbicacionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new CapturarUbicacionVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            tecnicoId: $request->tecnico_id,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precisionMetros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            fuente: $request->fuente,
        ));

        return response()->json($dto, 201);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/ObservacionVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\CreateObservacionVisitaCommand;
use App\Application\Visitas\Handlers\CreateObservacionVisitaHandler;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreObservacionVisitaRequest;
use Illuminate\Http\JsonResponse;

class ObservacionVisitaController extends Controller
{
    public function __construct(
        private readonly ObservacionVisitaRepositoryInterface $repository,
        private readonly CreateObservacionVisitaHandler $handler,
    ) {}

    public function index(string $visitaId): JsonResponse
    {
        return response()->json(['data' => $this->repository->listarPorVisita($visitaId)]);
    }

    public function store(StoreObservacionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new CreateObservacionVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            categoriaId: $request->categoria_id,
            tipo: $request->tipo,
            observacion: $request->observacion,
            createdBy: auth()->id(),
        ));

        return response()->json($dto, 201);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/FotoVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\SubirFotoVisitaCommand;
use App\Application\Visitas\Handlers\SubirFotoVisitaHandler;
use App\Application\Visitas\Services\FotoVisitaService;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\SubirFotoVisitaRequest;
use Illuminate\Http\JsonResponse;

class FotoVisitaController extends Controller
{
    public function __construct(
        private readonly FotoVisitaRepositoryInterface $repository,
        private readonly SubirFotoVisitaHandler $handler,
        private readonly FotoVisitaService $fotoService,
    ) {}

    public function store(SubirFotoVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->handler->handle(new SubirFotoVisitaCommand(
            visitaId: $visitaId,
            dispositivoId: $request->dispositivo_id,
            archivo: $request->file('archivo'),
            latitude: $request->latitude !== null ? (float) $request->latitude : null,
            longitude: $request->longitude !== null ? (float) $request->longitude : null,
        ));

        return response()->json($dto, 201);
    }

    public function show(string $visitaId, string $fotoId): JsonResponse
    {
        $model = $this->repository->findById($fotoId);

        return response()->json(
            \App\Application\Visitas\DTOs\FotoVisitaDTO::fromModel($model, $this->fotoService->urlFirmada($model->clave_almacenamiento))
        );
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/CapturarUbicacionVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapturarUbicacionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id'   => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'tecnico_id'       => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'fuente'           => ['required', Rule::in(['GPS', 'RED', 'MANUAL'])],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/StoreObservacionVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObservacionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'categoria_id'   => ['nullable', 'uuid', 'exists:pgsql_patrocinados.categorias_observaciones,id'],
            'tipo'           => ['required', Rule::in(['GENERAL', 'EDUCATIVA', 'SALUD', 'FAMILIAR'])],
            'observacion'    => ['required', 'string'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/SubirFotoVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class SubirFotoVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'archivo'        => ['required', 'file', 'mimes:jpg,jpeg,png,heic', 'max:20480'],
            'latitude'       => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'      => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
```

---

## 6d — Revisión y cierre (`revisiones_visitas`)

### Domain

#### app/Domain/Visitas/Contracts/RevisionVisitaRepositoryInterface.php
```php
<?php

namespace App\Domain\Visitas\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface RevisionVisitaRepositoryInterface
{
    public function create(array $data): mixed;
    public function listarPorVisita(string $visitaId): array;
    public function pendientesDeRevision(PaginationDTO $pagination): array;
}
```

### Application

#### app/Application/Visitas/DTOs/RevisionVisitaDTO.php
```php
<?php

namespace App\Application\Visitas\DTOs;

final readonly class RevisionVisitaDTO
{
    public function __construct(
        public string $id,
        public string $visitaId,
        public string $userId,
        public string $fechaRevision,
        public string $estado,
        public ?string $comentarios,
        public bool $requiereCorreccion,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            visitaId: $model->visita_id,
            userId: $model->user_id,
            fechaRevision: $model->fecha_revision?->toIso8601String(),
            estado: $model->estado,
            comentarios: $model->comentarios,
            requiereCorreccion: (bool) $model->requiere_correccion,
        );
    }
}
```

#### app/Application/Visitas/Commands/RevisarVisitaCommand.php
```php
<?php

namespace App\Application\Visitas\Commands;

final readonly class RevisarVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $userId,
        public string $estado, // APROBADA | RECHAZADA | REQUIERE_CORRECCION
        public ?string $comentarios,
    ) {}
}
```

#### app/Application/Visitas/Handlers/RevisarVisitaHandler.php
```php
<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Auditoria\Services\AuditoriaService;
use App\Application\Visitas\Commands\RevisarVisitaCommand;
use App\Application\Visitas\DTOs\RevisionVisitaDTO;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;
use App\Domain\Visitas\Contracts\VisitaRepositoryInterface;
use Illuminate\Support\Facades\DB;

/** Implementa la regla de sincronía #4: revisiones_visitas y visitas.estado_revision se actualizan juntos. */
class RevisarVisitaHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $revisionRepository,
        private readonly VisitaRepositoryInterface $visitaRepository,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function handle(RevisarVisitaCommand $command): RevisionVisitaDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $model = $this->revisionRepository->create([
                'visita_id'            => $command->visitaId,
                'user_id'              => $command->userId,
                'fecha_revision'       => now(),
                'estado'               => $command->estado,
                'comentarios'          => $command->comentarios,
                'requiere_correccion'  => $command->estado === 'REQUIERE_CORRECCION',
            ]);

            $this->visitaRepository->actualizarEstadoRevision($command->visitaId, $command->estado);

            $this->auditoria->registrar(
                userId: $command->userId,
                dispositivoId: null,
                accion: 'revisar',
                modulo: 'Visitas',
                tipoEntidad: 'visita',
                entidadId: $command->visitaId,
                valoresAnteriores: null,
                valoresNuevos: ['estado_revision' => $command->estado],
            );

            return RevisionVisitaDTO::fromModel($model);
        });
    }
}
```

#### app/Application/Visitas/Queries/GetRevisionesVisitaQuery.php
```php
<?php

namespace App\Application\Visitas\Queries;

final readonly class GetRevisionesVisitaQuery
{
    public function __construct(public string $visitaId) {}
}
```

#### app/Application/Visitas/Queries/GetVisitasPendientesDeRevisionQuery.php
```php
<?php

namespace App\Application\Visitas\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetVisitasPendientesDeRevisionQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### app/Application/Visitas/QueryHandlers/GetRevisionesVisitaQueryHandler.php
```php
<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetRevisionesVisitaQuery;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;

class GetRevisionesVisitaQueryHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetRevisionesVisitaQuery $query): array
    {
        return $this->repository->listarPorVisita($query->visitaId);
    }
}
```

#### app/Application/Visitas/QueryHandlers/GetVisitasPendientesDeRevisionQueryHandler.php
```php
<?php

namespace App\Application\Visitas\QueryHandlers;

use App\Application\Visitas\Queries\GetVisitasPendientesDeRevisionQuery;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;

class GetVisitasPendientesDeRevisionQueryHandler
{
    public function __construct(
        private readonly RevisionVisitaRepositoryInterface $repository
    ) {}

    public function handle(GetVisitasPendientesDeRevisionQuery $query): array
    {
        return $this->repository->pendientesDeRevision($query->pagination);
    }
}
```

### Infrastructure

#### app/Infrastructure/Visitas/Models/RevisionVisita.php
```php
<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RevisionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'revisiones_visitas';

    protected $fillable = ['visita_id', 'user_id', 'fecha_revision', 'estado', 'comentarios', 'requiere_correccion'];

    protected $casts = ['fecha_revision' => 'datetime', 'requiere_correccion' => 'boolean'];
}
```

#### app/Infrastructure/Visitas/Repositories/EloquentRevisionVisitaRepository.php
```php
<?php

namespace App\Infrastructure\Visitas\Repositories;

use App\Application\Visitas\DTOs\RevisionVisitaDTO;
use App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface;
use App\Infrastructure\Visitas\Models\RevisionVisita;
use App\Infrastructure\Visitas\Models\Visita;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentRevisionVisitaRepository implements RevisionVisitaRepositoryInterface
{
    public function create(array $data): mixed
    {
        return RevisionVisita::create($data);
    }

    public function listarPorVisita(string $visitaId): array
    {
        return RevisionVisita::where('visita_id', $visitaId)
            ->orderBy('fecha_revision', 'desc')
            ->get()
            ->map(fn ($m) => RevisionVisitaDTO::fromModel($m))
            ->all();
    }

    public function pendientesDeRevision(PaginationDTO $pagination): array
    {
        $paginated = Visita::where('estado_revision', 'PENDIENTE')
            ->where('estado', 'FINALIZADA')
            ->orderBy('fecha_finalizacion', 'asc')
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => collect($paginated->items())->map(fn ($v) => \App\Application\Visitas\DTOs\VisitaDTO::fromModel($v))->all(),
            'total' => $paginated->total(),
        ];
    }
}
```

### Http

#### app/Http/Controllers/Api/Patrocinados/RevisionVisitaController.php
```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Visitas\Commands\RevisarVisitaCommand;
use App\Application\Visitas\Handlers\RevisarVisitaHandler;
use App\Application\Visitas\Queries\GetRevisionesVisitaQuery;
use App\Application\Visitas\QueryHandlers\GetRevisionesVisitaQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Visitas\StoreRevisionVisitaRequest;
use Illuminate\Http\JsonResponse;

class RevisionVisitaController extends Controller
{
    public function __construct(
        private readonly GetRevisionesVisitaQueryHandler $getHandler,
        private readonly RevisarVisitaHandler $revisarHandler,
    ) {}

    public function index(string $visitaId): JsonResponse
    {
        return response()->json(['data' => $this->getHandler->handle(new GetRevisionesVisitaQuery($visitaId))]);
    }

    public function store(StoreRevisionVisitaRequest $request, string $visitaId): JsonResponse
    {
        $dto = $this->revisarHandler->handle(new RevisarVisitaCommand(
            visitaId: $visitaId,
            userId: auth()->id(),
            estado: $request->estado,
            comentarios: $request->comentarios,
        ));

        return response()->json($dto, 201);
    }
}
```

#### app/Http/Requests/Patrocinados/Visitas/StoreRevisionVisitaRequest.php
```php
<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRevisionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado'      => ['required', Rule::in(['APROBADA', 'RECHAZADA', 'REQUIERE_CORRECCION'])],
            'comentarios' => ['nullable', 'string'],
        ];
    }
}
```

---

## Bindings a agregar en `app/Providers/PatrocinadosServiceProvider.php`

```php
$this->app->bind(\App\Domain\Visitas\Contracts\MotivoVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentMotivoVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentCategoriaObservacionRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\PlanVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentPlanVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\VisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\AsignacionVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentAsignacionVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\HabilitacionVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentHabilitacionVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentUbicacionVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentObservacionVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentFotoVisitaRepository::class);
$this->app->bind(\App\Domain\Visitas\Contracts\RevisionVisitaRepositoryInterface::class, \App\Infrastructure\Visitas\Repositories\EloquentRevisionVisitaRepository::class);
```

## Rutas a agregar en `routes/api/patrocinados.php`

```php
Route::prefix('planes-visitas')->group(function () {
    Route::get('/', [PlanVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
    Route::post('/', [PlanVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
});

Route::prefix('visitas')->group(function () {
    Route::get('/', [VisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
    Route::post('/', [VisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.crear');
    Route::get('/{id}', [VisitaController::class, 'show'])->middleware('permiso-patrocinados:visitas.ver');
    Route::put('/{id}', [VisitaController::class, 'update'])->middleware('permiso-patrocinados:visitas.editar');
    Route::post('/{id}/reasignar', [VisitaController::class, 'reasignar'])->middleware('permiso-patrocinados:visitas.reasignar');
    Route::post('/{id}/iniciar', [VisitaController::class, 'iniciar'])->middleware('permiso-patrocinados:visitas.ejecutar');
    Route::post('/{id}/finalizar', [VisitaController::class, 'finalizar'])->middleware('permiso-patrocinados:visitas.ejecutar');
    Route::post('/{id}/reprogramar', [VisitaController::class, 'reprogramar'])->middleware('permiso-patrocinados:visitas.reprogramar');
    Route::post('/{id}/habilitaciones', [HabilitacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.habilitar');
    Route::post('/{id}/ubicaciones', [UbicacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.capturar-evidencia');
    Route::get('/{id}/observaciones', [ObservacionVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
    Route::post('/{id}/observaciones', [ObservacionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.capturar-evidencia');
    Route::post('/{id}/fotos', [FotoVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.capturar-evidencia');
    Route::get('/{id}/fotos/{fotoId}', [FotoVisitaController::class, 'show'])->middleware('permiso-patrocinados:visitas.ver');
    Route::get('/{id}/revisiones', [RevisionVisitaController::class, 'index'])->middleware('permiso-patrocinados:visitas.ver');
    Route::post('/{id}/revisiones', [RevisionVisitaController::class, 'store'])->middleware('permiso-patrocinados:visitas.revisar');
});

Route::post('/habilitaciones-visitas/{id}/revocar', [HabilitacionVisitaController::class, 'revocar'])
    ->middleware('permiso-patrocinados:visitas.habilitar');
```

## Pendientes explícitos que quedaron marcados en el código

1. `ReprogramarVisitaHandler::MAX_REPROGRAMACIONES` — valor y responsable de la decisión de baja aún no confirmados con negocio (docs/patrocinados/06-visitas.md, decisión #1).
2. `DarDeBajaPatrocinadoPorNoUbicadoHandler` — stub, bloqueado por la Etapa 5 (Patrocinados) y por la misma decisión de negocio.
3. `FotoVisitaService` — asume cifrado at-rest del disco; si negocio exige cifrado aplicativo por archivo, es el único punto a cambiar.
4. `FotoVisitaService::urlFirmada()` — devuelve `null` si el driver de Storage no soporta `temporaryUrl()`; falta decidir el endpoint de streaming autenticado como fallback (Etapa 9, hardening).

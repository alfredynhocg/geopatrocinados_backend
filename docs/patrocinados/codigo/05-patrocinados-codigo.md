# Código completo — Etapa 5: Patrocinados

> Complementa [../05-patrocinados.md](../05-patrocinados.md). Código PHP completo, listo para copiar, de cada archivo de la "Estructura DDD" de esa etapa. Fuente de columnas: las migraciones reales en `database/migrations/patrocinados/2026_09_01_00003{0,1,2,3,4}_*.php`.
>
> Convenciones aplicadas (ver `CLAUDE.md`): DTOs `final readonly` con `fromModel()`, Commands `final readonly`, un Handler por Command, Repositorios devuelven el modelo Eloquent (nunca el DTO), Controllers solo orquestan.
>
> **Reglas de negocio implementadas aquí** (no reabrir sin motivo, ver `docs/patrocinados/05-patrocinados.md`):
> 1. `UpdatePatrocinadoCommand` no tiene `comunidad_id`/`ubicacion_id` como propiedades — es estructuralmente imposible cambiar la ubicación por ese camino.
> 2. `CambiarUbicacionPatrocinadoHandler` es el único punto de escritura de `comunidad_id`/`ubicacion_id` + `historial_ubicaciones`, dentro de una transacción sobre `pgsql_patrocinados`.
> 3. `GetPatrocinadosQueryHandler`/`GetPatrocinadoByIdQueryHandler` deciden `PatrocinadoDTO` (completo) vs `PatrocinadoResumenDTO` consultando `auth()->user()?->can('patrocinados.ver-detalle')` — el Controller nunca recibe ni pasa ese flag.

---

## Domain/Patrocinados

#### `app/Domain/Patrocinados/Contracts/PatrocinadoRepositoryInterface.php`

```php
<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface PatrocinadoRepositoryInterface
{
    public function paginate(
        PaginationDTO $pagination,
        ?string $comunidadId,
        ?string $estadoId,
        ?string $nivelEducativo,
    ): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;

    /**
     * Único método autorizado para escribir comunidad_id/ubicacion_id.
     * Usado exclusivamente por CambiarUbicacionPatrocinadoHandler.
     */
    public function moverUbicacion(string $patrocinadoId, string $comunidadId, ?string $ubicacionId): mixed;
}
```

#### `app/Domain/Patrocinados/Contracts/TutorRepositoryInterface.php`

```php
<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface TutorRepositoryInterface
{
    public function paginateByPatrocinado(string $patrocinadoId, PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### `app/Domain/Patrocinados/Contracts/EstadoPatrocinadoRepositoryInterface.php`

```php
<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface EstadoPatrocinadoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### `app/Domain/Patrocinados/Contracts/TipoParentescoRepositoryInterface.php`

```php
<?php

namespace App\Domain\Patrocinados\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface TipoParentescoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### `app/Domain/Patrocinados/Contracts/HistorialUbicacionRepositoryInterface.php`

```php
<?php

namespace App\Domain\Patrocinados\Contracts;

interface HistorialUbicacionRepositoryInterface
{
    /** Timeline completo de un patrocinado, más reciente primero. */
    public function listByPatrocinado(string $patrocinadoId): array;

    /** La fila vigente (fecha_fin IS NULL), o null si nunca tuvo ubicación asignada. */
    public function findAbiertoByPatrocinado(string $patrocinadoId): mixed;

    /** Cierra la fila vigente poniendo fecha_fin = hoy. */
    public function cerrar(string $id): void;

    public function create(array $data): mixed;
}
```

#### `app/Domain/Patrocinados/Exceptions/PatrocinadoNotFoundException.php`

```php
<?php

namespace App\Domain\Patrocinados\Exceptions;

class PatrocinadoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Patrocinado '{$id}' no encontrado.", 404);
    }
}
```

#### `app/Domain/Patrocinados/Exceptions/TutorNotFoundException.php`

```php
<?php

namespace App\Domain\Patrocinados\Exceptions;

class TutorNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Tutor '{$id}' no encontrado.", 404);
    }
}
```

#### `app/Domain/Patrocinados/Exceptions/EstadoPatrocinadoNotFoundException.php`

```php
<?php

namespace App\Domain\Patrocinados\Exceptions;

class EstadoPatrocinadoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Estado de patrocinado '{$id}' no encontrado.", 404);
    }
}
```

---

## Application/Patrocinados

### DTOs

#### `app/Application/Patrocinados/DTOs/PatrocinadoDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class PatrocinadoDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
        public array $tutores,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            fecha_nacimiento: $model->fecha_nacimiento?->toDateString(),
            sexo: $model->sexo,
            comunidad_id: $model->comunidad_id,
            ubicacion_id: $model->ubicacion_id,
            unidad_educativa: $model->unidad_educativa,
            nivel_educativo: $model->nivel_educativo,
            estado_id: $model->estado_id,
            tutores: $model->relationLoaded('tutores')
                ? $model->tutores->map(fn (object $tutor) => TutorDTO::fromModel($tutor))->all()
                : [],
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### `app/Application/Patrocinados/DTOs/PatrocinadoResumenDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

/**
 * Variante sin datos sensibles del menor: sin tutores, sin dirección,
 * edad aproximada en vez de fecha de nacimiento exacta. Es el DTO por
 * defecto de cualquier listado — ver GetPatrocinadosQueryHandler.
 */
final readonly class PatrocinadoResumenDTO
{
    public function __construct(
        public string $id,
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?int $edad_aproximada,
        public string $comunidad_id,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            edad_aproximada: $model->fecha_nacimiento?->age,
            comunidad_id: $model->comunidad_id,
            nivel_educativo: $model->nivel_educativo,
            estado_id: $model->estado_id,
        );
    }
}
```

#### `app/Application/Patrocinados/DTOs/TutorDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class TutorDTO
{
    public function __construct(
        public string $id,
        public string $patrocinado_id,
        public string $nombres,
        public string $apellidos,
        public string $tipo_parentesco_id,
        public ?string $telefono,
        public string $direccion,
        public bool $estado,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            patrocinado_id: $model->patrocinado_id,
            nombres: $model->nombres,
            apellidos: $model->apellidos,
            tipo_parentesco_id: $model->tipo_parentesco_id,
            telefono: $model->telefono,
            direccion: $model->direccion,
            estado: (bool) $model->estado,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### `app/Application/Patrocinados/DTOs/EstadoPatrocinadoDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class EstadoPatrocinadoDTO
{
    public function __construct(
        public string $id,
        public string $estado,
        public ?string $created_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            estado: $model->estado,
            created_at: $model->created_at?->toIso8601String(),
        );
    }
}
```

#### `app/Application/Patrocinados/DTOs/TipoParentescoDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class TipoParentescoDTO
{
    public function __construct(
        public string $id,
        public string $parentesco,
        public bool $estado,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            parentesco: $model->parentesco,
            estado: (bool) $model->estado,
        );
    }
}
```

#### `app/Application/Patrocinados/DTOs/HistorialUbicacionDTO.php`

```php
<?php

namespace App\Application\Patrocinados\DTOs;

final readonly class HistorialUbicacionDTO
{
    public function __construct(
        public string $id,
        public string $patrocinado_id,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public string $fecha_inicio,
        public ?string $fecha_fin,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            patrocinado_id: $model->patrocinado_id,
            comunidad_id: $model->comunidad_id,
            ubicacion_id: $model->ubicacion_id,
            fecha_inicio: $model->fecha_inicio->toDateString(),
            fecha_fin: $model->fecha_fin?->toDateString(),
        );
    }
}
```

### Commands

#### `app/Application/Patrocinados/Commands/CreatePatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreatePatrocinadoCommand
{
    public function __construct(
        public string $codigo,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/UpdatePatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

/**
 * Deliberadamente SIN comunidad_id/ubicacion_id: el único camino válido
 * para mover a un patrocinado es CambiarUbicacionPatrocinadoCommand.
 */
final readonly class UpdatePatrocinadoCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public ?string $apellidos,
        public ?string $fecha_nacimiento,
        public ?string $sexo,
        public ?string $unidad_educativa,
        public ?string $nivel_educativo,
        public string $estado_id,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/DeletePatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeletePatrocinadoCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Patrocinados/Commands/CambiarUbicacionPatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CambiarUbicacionPatrocinadoCommand
{
    public function __construct(
        public string $patrocinado_id,
        public string $comunidad_id,
        public ?string $ubicacion_id,
        public ?string $usuario_id,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/CreateTutorCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreateTutorCommand
{
    public function __construct(
        public string $patrocinado_id,
        public string $nombres,
        public string $apellidos,
        public string $tipo_parentesco_id,
        public ?string $telefono,
        public string $direccion,
        public bool $estado,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/UpdateTutorCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateTutorCommand
{
    public function __construct(
        public string $id,
        public string $nombres,
        public string $apellidos,
        public string $tipo_parentesco_id,
        public ?string $telefono,
        public string $direccion,
        public bool $estado,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/DeleteTutorCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeleteTutorCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Patrocinados/Commands/CreateEstadoPatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreateEstadoPatrocinadoCommand
{
    public function __construct(public string $estado) {}
}
```

#### `app/Application/Patrocinados/Commands/UpdateEstadoPatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateEstadoPatrocinadoCommand
{
    public function __construct(
        public string $id,
        public string $estado,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/DeleteEstadoPatrocinadoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeleteEstadoPatrocinadoCommand
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Patrocinados/Commands/CreateTipoParentescoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class CreateTipoParentescoCommand
{
    public function __construct(
        public string $parentesco,
        public bool $estado,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/UpdateTipoParentescoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class UpdateTipoParentescoCommand
{
    public function __construct(
        public string $id,
        public string $parentesco,
        public bool $estado,
    ) {}
}
```

#### `app/Application/Patrocinados/Commands/DeleteTipoParentescoCommand.php`

```php
<?php

namespace App\Application\Patrocinados\Commands;

final readonly class DeleteTipoParentescoCommand
{
    public function __construct(public string $id) {}
}
```

### Handlers

#### `app/Application/Patrocinados/Handlers/CreatePatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreatePatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class CreatePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(CreatePatrocinadoCommand $command): PatrocinadoDTO
    {
        $model = $this->repository->create([
            'codigo'            => $command->codigo,
            'nombres'           => $command->nombres,
            'apellidos'         => $command->apellidos,
            'fecha_nacimiento'  => $command->fecha_nacimiento,
            'sexo'              => $command->sexo,
            'comunidad_id'      => $command->comunidad_id,
            'ubicacion_id'      => $command->ubicacion_id,
            'unidad_educativa'  => $command->unidad_educativa,
            'nivel_educativo'   => $command->nivel_educativo,
            'estado_id'         => $command->estado_id,
        ]);

        return PatrocinadoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/UpdatePatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdatePatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class UpdatePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    /**
     * No recibe ni puede recibir comunidad_id/ubicacion_id: el Command no
     * declara esas propiedades. Para mover a un patrocinado usar
     * CambiarUbicacionPatrocinadoHandler.
     */
    public function handle(UpdatePatrocinadoCommand $command): PatrocinadoDTO
    {
        $model = $this->repository->update($command->id, [
            'nombres'           => $command->nombres,
            'apellidos'         => $command->apellidos,
            'fecha_nacimiento'  => $command->fecha_nacimiento,
            'sexo'              => $command->sexo,
            'unidad_educativa'  => $command->unidad_educativa,
            'nivel_educativo'   => $command->nivel_educativo,
            'estado_id'         => $command->estado_id,
        ]);

        return PatrocinadoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/DeletePatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeletePatrocinadoCommand;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class DeletePatrocinadoHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(DeletePatrocinadoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/Patrocinados/Handlers/CambiarUbicacionPatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CambiarUbicacionPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Único camino válido para cambiar comunidad_id/ubicacion_id de un
 * patrocinado. Implementa la regla de sincronía patrocinados <-> historial_ubicaciones
 * (plan de revisión §8.1): cierra la fila vigente, abre la nueva, y
 * actualiza el "estado actual" en patrocinados, todo en la misma transacción.
 */
class CambiarUbicacionPatrocinadoHandler
{
    public function __construct(
        private readonly PatrocinadoRepositoryInterface $patrocinadoRepository,
        private readonly HistorialUbicacionRepositoryInterface $historialRepository,
    ) {}

    public function handle(CambiarUbicacionPatrocinadoCommand $command): PatrocinadoDTO
    {
        return DB::connection('pgsql_patrocinados')->transaction(function () use ($command) {
            $abierta = $this->historialRepository->findAbiertoByPatrocinado($command->patrocinado_id);

            if ($abierta !== null) {
                $this->historialRepository->cerrar($abierta->id);
            }

            $this->historialRepository->create([
                'patrocinado_id' => $command->patrocinado_id,
                'comunidad_id'   => $command->comunidad_id,
                'ubicacion_id'   => $command->ubicacion_id,
                'fecha_inicio'   => now()->toDateString(),
                'fecha_fin'      => null,
                'created_by'     => $command->usuario_id,
            ]);

            $patrocinado = $this->patrocinadoRepository->moverUbicacion(
                $command->patrocinado_id,
                $command->comunidad_id,
                $command->ubicacion_id,
            );

            return PatrocinadoDTO::fromModel($patrocinado);
        });
    }
}
```

#### `app/Application/Patrocinados/Handlers/CreateTutorHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateTutorCommand;
use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class CreateTutorHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(CreateTutorCommand $command): TutorDTO
    {
        $model = $this->repository->create([
            'patrocinado_id'      => $command->patrocinado_id,
            'nombres'             => $command->nombres,
            'apellidos'           => $command->apellidos,
            'tipo_parentesco_id'  => $command->tipo_parentesco_id,
            'telefono'            => $command->telefono,
            'direccion'           => $command->direccion,
            'estado'              => $command->estado,
        ]);

        return TutorDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/UpdateTutorHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdateTutorCommand;
use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class UpdateTutorHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(UpdateTutorCommand $command): TutorDTO
    {
        $model = $this->repository->update($command->id, [
            'nombres'             => $command->nombres,
            'apellidos'           => $command->apellidos,
            'tipo_parentesco_id'  => $command->tipo_parentesco_id,
            'telefono'            => $command->telefono,
            'direccion'           => $command->direccion,
            'estado'              => $command->estado,
        ]);

        return TutorDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/DeleteTutorHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteTutorCommand;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class DeleteTutorHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(DeleteTutorCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/Patrocinados/Handlers/CreateEstadoPatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class CreateEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(CreateEstadoPatrocinadoCommand $command): EstadoPatrocinadoDTO
    {
        $model = $this->repository->create(['estado' => $command->estado]);

        return EstadoPatrocinadoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/UpdateEstadoPatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class UpdateEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(UpdateEstadoPatrocinadoCommand $command): EstadoPatrocinadoDTO
    {
        $model = $this->repository->update($command->id, ['estado' => $command->estado]);

        return EstadoPatrocinadoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/DeleteEstadoPatrocinadoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteEstadoPatrocinadoCommand;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class DeleteEstadoPatrocinadoHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(DeleteEstadoPatrocinadoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### `app/Application/Patrocinados/Handlers/CreateTipoParentescoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateTipoParentescoCommand;
use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class CreateTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(CreateTipoParentescoCommand $command): TipoParentescoDTO
    {
        $model = $this->repository->create([
            'parentesco' => $command->parentesco,
            'estado'     => $command->estado,
        ]);

        return TipoParentescoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/UpdateTipoParentescoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdateTipoParentescoCommand;
use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class UpdateTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(UpdateTipoParentescoCommand $command): TipoParentescoDTO
    {
        $model = $this->repository->update($command->id, [
            'parentesco' => $command->parentesco,
            'estado'     => $command->estado,
        ]);

        return TipoParentescoDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/Handlers/DeleteTipoParentescoHandler.php`

```php
<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\DeleteTipoParentescoCommand;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class DeleteTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(DeleteTipoParentescoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

### Queries

#### `app/Application/Patrocinados/Queries/GetPatrocinadosQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetPatrocinadosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $comunidad_id = null,
        public ?string $estado_id = null,
        public ?string $nivel_educativo = null,
    ) {}
}
```

#### `app/Application/Patrocinados/Queries/GetPatrocinadoByIdQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetPatrocinadoByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Patrocinados/Queries/GetHistorialUbicacionesQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetHistorialUbicacionesQuery
{
    public function __construct(public string $patrocinado_id) {}
}
```

#### `app/Application/Patrocinados/Queries/GetTutoresByPatrocinadoQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetTutoresByPatrocinadoQuery
{
    public function __construct(
        public string $patrocinado_id,
        public PaginationDTO $pagination,
    ) {}
}
```

#### `app/Application/Patrocinados/Queries/GetTutorByIdQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

final readonly class GetTutorByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### `app/Application/Patrocinados/Queries/GetEstadosPatrocinadoQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetEstadosPatrocinadoQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### `app/Application/Patrocinados/Queries/GetTiposParentescoQuery.php`

```php
<?php

namespace App\Application\Patrocinados\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetTiposParentescoQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

### QueryHandlers

#### `app/Application/Patrocinados/QueryHandlers/GetPatrocinadosQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Application\Patrocinados\DTOs\PatrocinadoResumenDTO;
use App\Application\Patrocinados\Queries\GetPatrocinadosQuery;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class GetPatrocinadosQueryHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(GetPatrocinadosQuery $query): array
    {
        $paginated = $this->repository->paginate(
            $query->pagination,
            $query->comunidad_id,
            $query->estado_id,
            $query->nivel_educativo,
        );

        // Dato sensible de menor de edad: sin el permiso, siempre el resumen.
        $conDetalle = (bool) auth()->user()?->can('patrocinados.ver-detalle');

        return [
            'data' => collect($paginated['data'])
                ->map(fn (object $model) => $conDetalle
                    ? PatrocinadoDTO::fromModel($model)
                    : PatrocinadoResumenDTO::fromModel($model))
                ->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetPatrocinadoByIdQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\PatrocinadoDTO;
use App\Application\Patrocinados\DTOs\PatrocinadoResumenDTO;
use App\Application\Patrocinados\Queries\GetPatrocinadoByIdQuery;
use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;

class GetPatrocinadoByIdQueryHandler
{
    public function __construct(private readonly PatrocinadoRepositoryInterface $repository) {}

    public function handle(GetPatrocinadoByIdQuery $query): PatrocinadoDTO|PatrocinadoResumenDTO
    {
        $model = $this->repository->findById($query->id);

        $conDetalle = (bool) auth()->user()?->can('patrocinados.ver-detalle');

        return $conDetalle ? PatrocinadoDTO::fromModel($model) : PatrocinadoResumenDTO::fromModel($model);
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetHistorialUbicacionesQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\HistorialUbicacionDTO;
use App\Application\Patrocinados\Queries\GetHistorialUbicacionesQuery;
use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;

class GetHistorialUbicacionesQueryHandler
{
    public function __construct(private readonly HistorialUbicacionRepositoryInterface $repository) {}

    public function handle(GetHistorialUbicacionesQuery $query): array
    {
        $rows = $this->repository->listByPatrocinado($query->patrocinado_id);

        return array_map(fn (object $model) => HistorialUbicacionDTO::fromModel($model), $rows);
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetTutoresByPatrocinadoQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Application\Patrocinados\Queries\GetTutoresByPatrocinadoQuery;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class GetTutoresByPatrocinadoQueryHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(GetTutoresByPatrocinadoQuery $query): array
    {
        $paginated = $this->repository->paginateByPatrocinado($query->patrocinado_id, $query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => TutorDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetTutorByIdQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TutorDTO;
use App\Application\Patrocinados\Queries\GetTutorByIdQuery;
use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;

class GetTutorByIdQueryHandler
{
    public function __construct(private readonly TutorRepositoryInterface $repository) {}

    public function handle(GetTutorByIdQuery $query): TutorDTO
    {
        return TutorDTO::fromModel($this->repository->findById($query->id));
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetEstadosPatrocinadoQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\EstadoPatrocinadoDTO;
use App\Application\Patrocinados\Queries\GetEstadosPatrocinadoQuery;
use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;

class GetEstadosPatrocinadoQueryHandler
{
    public function __construct(private readonly EstadoPatrocinadoRepositoryInterface $repository) {}

    public function handle(GetEstadosPatrocinadoQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => EstadoPatrocinadoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

#### `app/Application/Patrocinados/QueryHandlers/GetTiposParentescoQueryHandler.php`

```php
<?php

namespace App\Application\Patrocinados\QueryHandlers;

use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Application\Patrocinados\Queries\GetTiposParentescoQuery;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class GetTiposParentescoQueryHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(GetTiposParentescoQuery $query): array
    {
        $paginated = $this->repository->paginate($query->pagination);

        return [
            'data'  => collect($paginated['data'])->map(fn (object $m) => TipoParentescoDTO::fromModel($m))->all(),
            'total' => $paginated['total'],
        ];
    }
}
```

---

## Infrastructure/Patrocinados

### Models

#### `app/Infrastructure/Patrocinados/Models/EstadoPatrocinado.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoPatrocinado extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'estados_patrocinados';

    protected $fillable = ['estado', 'updated_by'];

    public function patrocinados()
    {
        return $this->hasMany(Patrocinado::class, 'estado_id');
    }
}
```

#### `app/Infrastructure/Patrocinados/Models/TipoParentesco.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TipoParentesco extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'tipos_parentescos';

    protected $fillable = ['parentesco', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}
```

#### `app/Infrastructure/Patrocinados/Models/Patrocinado.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patrocinado extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'patrocinados';

    protected $fillable = [
        'codigo', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo',
        'comunidad_id', 'ubicacion_id', 'unidad_educativa', 'nivel_educativo',
        'estado_id', 'updated_by',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function tutores()
    {
        return $this->hasMany(Tutor::class, 'patrocinado_id');
    }

    public function estadoPatrocinado()
    {
        return $this->belongsTo(EstadoPatrocinado::class, 'estado_id');
    }

    public function historialUbicaciones()
    {
        return $this->hasMany(HistorialUbicacion::class, 'patrocinado_id');
    }
}
```

#### `app/Infrastructure/Patrocinados/Models/Tutor.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutor extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'tutores';

    protected $fillable = [
        'patrocinado_id', 'nombres', 'apellidos', 'tipo_parentesco_id',
        'telefono', 'direccion', 'estado', 'updated_by',
    ];

    protected $casts = ['estado' => 'boolean'];

    public function patrocinado()
    {
        return $this->belongsTo(Patrocinado::class, 'patrocinado_id');
    }

    public function tipoParentesco()
    {
        return $this->belongsTo(TipoParentesco::class, 'tipo_parentesco_id');
    }
}
```

#### `app/Infrastructure/Patrocinados/Models/HistorialUbicacion.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Sin SoftDeletes: el docx no marca deleted_at en esta tabla y la
 * hoja de ruta prohíbe agregarlo por analogía con `patrocinados`.
 */
class HistorialUbicacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'historial_ubicaciones';

    protected $fillable = [
        'patrocinado_id', 'comunidad_id', 'ubicacion_id',
        'fecha_inicio', 'fecha_fin', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function patrocinado()
    {
        return $this->belongsTo(Patrocinado::class, 'patrocinado_id');
    }
}
```

### Repositories

#### `app/Infrastructure/Patrocinados/Repositories/EloquentPatrocinadoRepository.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\PatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\PatrocinadoNotFoundException;
use App\Infrastructure\Patrocinados\Models\Patrocinado;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentPatrocinadoRepository implements PatrocinadoRepositoryInterface
{
    public function paginate(
        PaginationDTO $pagination,
        ?string $comunidadId,
        ?string $estadoId,
        ?string $nivelEducativo,
    ): array {
        $q = Patrocinado::query()->whereNull('deleted_at');

        if ($comunidadId) {
            $q->where('comunidad_id', $comunidadId);
        }
        if ($estadoId) {
            $q->where('estado_id', $estadoId);
        }
        if ($nivelEducativo) {
            $q->where('nivel_educativo', $nivelEducativo);
        }
        if ($pagination->query !== '') {
            $q->where(fn ($sub) => $sub
                ->where('codigo', 'ilike', "%{$pagination->query}%")
                ->orWhere('nombres', 'ilike', "%{$pagination->query}%")
                ->orWhere('apellidos', 'ilike', "%{$pagination->query}%"));
        }

        $paginated = $q->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $patrocinado = Patrocinado::with('tutores')->whereNull('deleted_at')->find($id);

        if (! $patrocinado) {
            throw new PatrocinadoNotFoundException($id);
        }

        return $patrocinado;
    }

    public function create(array $data): mixed
    {
        return Patrocinado::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $patrocinado = $this->findById($id);
        $patrocinado->update($data);

        return $patrocinado->fresh('tutores');
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Patrocinado::whereIn('id', (array) $ids)->delete();
    }

    public function moverUbicacion(string $patrocinadoId, string $comunidadId, ?string $ubicacionId): mixed
    {
        $patrocinado = $this->findById($patrocinadoId);

        $patrocinado->update([
            'comunidad_id' => $comunidadId,
            'ubicacion_id' => $ubicacionId,
        ]);

        return $patrocinado->fresh('tutores');
    }
}
```

#### `app/Infrastructure/Patrocinados/Repositories/EloquentTutorRepository.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\TutorRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\TutorNotFoundException;
use App\Infrastructure\Patrocinados\Models\Tutor;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentTutorRepository implements TutorRepositoryInterface
{
    public function paginateByPatrocinado(string $patrocinadoId, PaginationDTO $pagination): array
    {
        $paginated = Tutor::query()
            ->where('patrocinado_id', $patrocinadoId)
            ->whereNull('deleted_at')
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $tutor = Tutor::whereNull('deleted_at')->find($id);

        if (! $tutor) {
            throw new TutorNotFoundException($id);
        }

        return $tutor;
    }

    public function create(array $data): mixed
    {
        return Tutor::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $tutor = $this->findById($id);
        $tutor->update($data);

        return $tutor->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Tutor::whereIn('id', (array) $ids)->delete();
    }
}
```

#### `app/Infrastructure/Patrocinados/Repositories/EloquentEstadoPatrocinadoRepository.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\EstadoPatrocinadoRepositoryInterface;
use App\Domain\Patrocinados\Exceptions\EstadoPatrocinadoNotFoundException;
use App\Infrastructure\Patrocinados\Models\EstadoPatrocinado;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentEstadoPatrocinadoRepository implements EstadoPatrocinadoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = EstadoPatrocinado::query()
            ->whereNull('deleted_at')
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        $estado = EstadoPatrocinado::whereNull('deleted_at')->find($id);

        if (! $estado) {
            throw new EstadoPatrocinadoNotFoundException($id);
        }

        return $estado;
    }

    public function create(array $data): mixed
    {
        return EstadoPatrocinado::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $estado = $this->findById($id);
        $estado->update($data);

        return $estado->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) EstadoPatrocinado::whereIn('id', (array) $ids)->delete();
    }
}
```

#### `app/Infrastructure/Patrocinados/Repositories/EloquentTipoParentescoRepository.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;
use App\Infrastructure\Patrocinados\Models\TipoParentesco;
use App\Shared\Kernel\DTOs\PaginationDTO;

class EloquentTipoParentescoRepository implements TipoParentescoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $paginated = TipoParentesco::query()
            ->orderBy($pagination->sortKey !== '' ? $pagination->sortKey : 'created_at', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data'  => $paginated->items(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): mixed
    {
        return TipoParentesco::findOrFail($id);
    }

    public function create(array $data): mixed
    {
        return TipoParentesco::create($data);
    }

    public function update(string $id, array $data): mixed
    {
        $tipo = $this->findById($id);
        $tipo->update($data);

        return $tipo->fresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) TipoParentesco::whereIn('id', (array) $ids)->delete();
    }
}
```

#### `app/Infrastructure/Patrocinados/Repositories/EloquentHistorialUbicacionRepository.php`

```php
<?php

namespace App\Infrastructure\Patrocinados\Repositories;

use App\Domain\Patrocinados\Contracts\HistorialUbicacionRepositoryInterface;
use App\Infrastructure\Patrocinados\Models\HistorialUbicacion;

class EloquentHistorialUbicacionRepository implements HistorialUbicacionRepositoryInterface
{
    public function listByPatrocinado(string $patrocinadoId): array
    {
        return HistorialUbicacion::where('patrocinado_id', $patrocinadoId)
            ->orderByDesc('fecha_inicio')
            ->get()
            ->all();
    }

    public function findAbiertoByPatrocinado(string $patrocinadoId): mixed
    {
        return HistorialUbicacion::where('patrocinado_id', $patrocinadoId)
            ->whereNull('fecha_fin')
            ->first();
    }

    public function cerrar(string $id): void
    {
        HistorialUbicacion::whereKey($id)->update(['fecha_fin' => now()->toDateString()]);
    }

    public function create(array $data): mixed
    {
        return HistorialUbicacion::create($data);
    }
}
```

---

## Http

### Controllers

#### `app/Http/Controllers/Api/Patrocinados/PatrocinadoController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CambiarUbicacionPatrocinadoCommand;
use App\Application\Patrocinados\Commands\CreatePatrocinadoCommand;
use App\Application\Patrocinados\Commands\DeletePatrocinadoCommand;
use App\Application\Patrocinados\Commands\UpdatePatrocinadoCommand;
use App\Application\Patrocinados\Handlers\CambiarUbicacionPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\CreatePatrocinadoHandler;
use App\Application\Patrocinados\Handlers\DeletePatrocinadoHandler;
use App\Application\Patrocinados\Handlers\UpdatePatrocinadoHandler;
use App\Application\Patrocinados\Queries\GetHistorialUbicacionesQuery;
use App\Application\Patrocinados\Queries\GetPatrocinadoByIdQuery;
use App\Application\Patrocinados\Queries\GetPatrocinadosQuery;
use App\Application\Patrocinados\QueryHandlers\GetHistorialUbicacionesQueryHandler;
use App\Application\Patrocinados\QueryHandlers\GetPatrocinadoByIdQueryHandler;
use App\Application\Patrocinados\QueryHandlers\GetPatrocinadosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\CambiarUbicacionPatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\StorePatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdatePatrocinadoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrocinadoController extends Controller
{
    public function __construct(
        private readonly GetPatrocinadosQueryHandler $getPatrocinadosHandler,
        private readonly GetPatrocinadoByIdQueryHandler $getPatrocinadoByIdHandler,
        private readonly GetHistorialUbicacionesQueryHandler $getHistorialHandler,
        private readonly CreatePatrocinadoHandler $createHandler,
        private readonly UpdatePatrocinadoHandler $updateHandler,
        private readonly DeletePatrocinadoHandler $deleteHandler,
        private readonly CambiarUbicacionPatrocinadoHandler $cambiarUbicacionHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getPatrocinadosHandler->handle(new GetPatrocinadosQuery(
            pagination: $pagination,
            comunidad_id: $request->get('comunidad_id'),
            estado_id: $request->get('estado_id'),
            nivel_educativo: $request->get('nivel_educativo'),
        )));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->getPatrocinadoByIdHandler->handle(new GetPatrocinadoByIdQuery($id)));
    }

    public function store(StorePatrocinadoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreatePatrocinadoCommand(
            codigo: $request->codigo,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            fecha_nacimiento: $request->fecha_nacimiento,
            sexo: $request->sexo,
            comunidad_id: $request->comunidad_id,
            ubicacion_id: $request->ubicacion_id,
            unidad_educativa: $request->unidad_educativa,
            nivel_educativo: $request->nivel_educativo,
            estado_id: $request->estado_id,
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdatePatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdatePatrocinadoCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            fecha_nacimiento: $request->fecha_nacimiento,
            sexo: $request->sexo,
            unidad_educativa: $request->unidad_educativa,
            nivel_educativo: $request->nivel_educativo,
            estado_id: $request->estado_id,
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeletePatrocinadoCommand($id));

        return response()->json(null, 204);
    }

    public function cambiarUbicacion(CambiarUbicacionPatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->cambiarUbicacionHandler->handle(new CambiarUbicacionPatrocinadoCommand(
            patrocinado_id: $id,
            comunidad_id: $request->comunidad_id,
            ubicacion_id: $request->ubicacion_id,
            usuario_id: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function historialUbicaciones(string $id): JsonResponse
    {
        return response()->json($this->getHistorialHandler->handle(new GetHistorialUbicacionesQuery($id)));
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/TutorController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateTutorCommand;
use App\Application\Patrocinados\Commands\DeleteTutorCommand;
use App\Application\Patrocinados\Commands\UpdateTutorCommand;
use App\Application\Patrocinados\Handlers\CreateTutorHandler;
use App\Application\Patrocinados\Handlers\DeleteTutorHandler;
use App\Application\Patrocinados\Handlers\UpdateTutorHandler;
use App\Application\Patrocinados\Queries\GetTutoresByPatrocinadoQuery;
use App\Application\Patrocinados\QueryHandlers\GetTutoresByPatrocinadoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreTutorRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateTutorRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TutorController extends Controller
{
    public function __construct(
        private readonly GetTutoresByPatrocinadoQueryHandler $getTutoresHandler,
        private readonly CreateTutorHandler $createHandler,
        private readonly UpdateTutorHandler $updateHandler,
        private readonly DeleteTutorHandler $deleteHandler,
    ) {}

    public function index(Request $request, string $patrocinadoId): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getTutoresHandler->handle(
            new GetTutoresByPatrocinadoQuery($patrocinadoId, $pagination)
        ));
    }

    public function store(StoreTutorRequest $request, string $patrocinadoId): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateTutorCommand(
            patrocinado_id: $patrocinadoId,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            tipo_parentesco_id: $request->tipo_parentesco_id,
            telefono: $request->telefono,
            direccion: $request->direccion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateTutorRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateTutorCommand(
            id: $id,
            nombres: $request->nombres,
            apellidos: $request->apellidos,
            tipo_parentesco_id: $request->tipo_parentesco_id,
            telefono: $request->telefono,
            direccion: $request->direccion,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteTutorCommand($id));

        return response()->json(null, 204);
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/EstadoPatrocinadoController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Commands\DeleteEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Commands\UpdateEstadoPatrocinadoCommand;
use App\Application\Patrocinados\Handlers\CreateEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\DeleteEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Handlers\UpdateEstadoPatrocinadoHandler;
use App\Application\Patrocinados\Queries\GetEstadosPatrocinadoQuery;
use App\Application\Patrocinados\QueryHandlers\GetEstadosPatrocinadoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreEstadoPatrocinadoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateEstadoPatrocinadoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EstadoPatrocinadoController extends Controller
{
    public function __construct(
        private readonly GetEstadosPatrocinadoQueryHandler $getEstadosHandler,
        private readonly CreateEstadoPatrocinadoHandler $createHandler,
        private readonly UpdateEstadoPatrocinadoHandler $updateHandler,
        private readonly DeleteEstadoPatrocinadoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getEstadosHandler->handle(new GetEstadosPatrocinadoQuery($pagination)));
    }

    public function store(StoreEstadoPatrocinadoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateEstadoPatrocinadoCommand(estado: $request->estado));

        return response()->json($dto, 201);
    }

    public function update(UpdateEstadoPatrocinadoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateEstadoPatrocinadoCommand(id: $id, estado: $request->estado));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteEstadoPatrocinadoCommand($id));

        return response()->json(null, 204);
    }
}
```

#### `app/Http/Controllers/Api/Patrocinados/TipoParentescoController.php`

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Patrocinados\Commands\CreateTipoParentescoCommand;
use App\Application\Patrocinados\Commands\DeleteTipoParentescoCommand;
use App\Application\Patrocinados\Commands\UpdateTipoParentescoCommand;
use App\Application\Patrocinados\Handlers\CreateTipoParentescoHandler;
use App\Application\Patrocinados\Handlers\DeleteTipoParentescoHandler;
use App\Application\Patrocinados\Handlers\UpdateTipoParentescoHandler;
use App\Application\Patrocinados\Queries\GetTiposParentescoQuery;
use App\Application\Patrocinados\QueryHandlers\GetTiposParentescoQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Patrocinados\StoreTipoParentescoRequest;
use App\Http\Requests\Patrocinados\Patrocinados\UpdateTipoParentescoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipoParentescoController extends Controller
{
    public function __construct(
        private readonly GetTiposParentescoQueryHandler $getTiposHandler,
        private readonly CreateTipoParentescoHandler $createHandler,
        private readonly UpdateTipoParentescoHandler $updateHandler,
        private readonly DeleteTipoParentescoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all());

        return response()->json($this->getTiposHandler->handle(new GetTiposParentescoQuery($pagination)));
    }

    public function store(StoreTipoParentescoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateTipoParentescoCommand(
            parentesco: $request->parentesco,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateTipoParentescoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateTipoParentescoCommand(
            id: $id,
            parentesco: $request->parentesco,
            estado: $request->boolean('estado', true),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteTipoParentescoCommand($id));

        return response()->json(null, 204);
    }
}
```

### Requests

#### `app/Http/Requests/Patrocinados/Patrocinados/StorePatrocinadoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StorePatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'            => ['required', 'string', 'max:60', 'unique:pgsql_patrocinados.patrocinados,codigo'],
            'nombres'           => ['required', 'string', 'max:120'],
            'apellidos'         => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento'  => ['nullable', 'date'],
            'sexo'              => ['nullable', 'string', 'max:30'],
            'comunidad_id'      => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'ubicacion_id'      => ['nullable', 'uuid', 'exists:pgsql_patrocinados.ubicaciones,id'],
            'unidad_educativa'  => ['nullable', 'string', 'max:200'],
            'nivel_educativo'   => ['nullable', 'string', 'max:120'],
            'estado_id'         => ['required', 'uuid', 'exists:pgsql_patrocinados.estados_patrocinados,id'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/UpdatePatrocinadoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberadamente sin comunidad_id/ubicacion_id/codigo: cambiar la
 * ubicación de un patrocinado va por CambiarUbicacionPatrocinadoRequest.
 */
class UpdatePatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'           => ['required', 'string', 'max:120'],
            'apellidos'         => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento'  => ['nullable', 'date'],
            'sexo'              => ['nullable', 'string', 'max:30'],
            'unidad_educativa'  => ['nullable', 'string', 'max:200'],
            'nivel_educativo'   => ['nullable', 'string', 'max:120'],
            'estado_id'         => ['required', 'uuid', 'exists:pgsql_patrocinados.estados_patrocinados,id'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/CambiarUbicacionPatrocinadoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class CambiarUbicacionPatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comunidad_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'ubicacion_id' => ['nullable', 'uuid', 'exists:pgsql_patrocinados.ubicaciones,id'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/StoreTutorRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StoreTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'             => ['required', 'string', 'max:120'],
            'apellidos'           => ['required', 'string', 'max:160'],
            'tipo_parentesco_id'  => ['required', 'uuid', 'exists:pgsql_patrocinados.tipos_parentescos,id'],
            'telefono'            => ['nullable', 'string', 'max:40'],
            'direccion'           => ['required', 'string', 'max:160'],
            'estado'              => ['boolean'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/UpdateTutorRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'             => ['required', 'string', 'max:120'],
            'apellidos'           => ['required', 'string', 'max:160'],
            'tipo_parentesco_id'  => ['required', 'uuid', 'exists:pgsql_patrocinados.tipos_parentescos,id'],
            'telefono'            => ['nullable', 'string', 'max:40'],
            'direccion'           => ['required', 'string', 'max:160'],
            'estado'              => ['boolean'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/StoreEstadoPatrocinadoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstadoPatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(['ACTIVO', 'NO_ENCONTRADO', 'INACTIVO_NO_UBICADO', 'MAYOR_DE_EDAD'])],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/UpdateEstadoPatrocinadoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEstadoPatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(['ACTIVO', 'NO_ENCONTRADO', 'INACTIVO_NO_UBICADO', 'MAYOR_DE_EDAD'])],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/StoreTipoParentescoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StoreTipoParentescoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parentesco' => ['required', 'string', 'max:120'],
            'estado'     => ['boolean'],
        ];
    }
}
```

#### `app/Http/Requests/Patrocinados/Patrocinados/UpdateTipoParentescoRequest.php`

```php
<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoParentescoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parentesco' => ['required', 'string', 'max:120'],
            'estado'     => ['boolean'],
        ];
    }
}
```

---

## Rutas de referencia (para `routes/api/patrocinados.php`, se cablean formalmente en la Etapa 1/9)

```php
Route::prefix('ninos')->group(function () {
    Route::get('/', [PatrocinadoController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
    Route::post('/', [PatrocinadoController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.crear');
    Route::get('/{id}', [PatrocinadoController::class, 'show'])->middleware('permiso-patrocinados:patrocinados.ver');
    Route::put('/{id}', [PatrocinadoController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
    Route::delete('/{id}', [PatrocinadoController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.eliminar');
    Route::post('/{id}/cambiar-ubicacion', [PatrocinadoController::class, 'cambiarUbicacion'])->middleware('permiso-patrocinados:patrocinados.editar');
    Route::get('/{id}/historial-ubicaciones', [PatrocinadoController::class, 'historialUbicaciones'])->middleware('permiso-patrocinados:patrocinados.ver');
    Route::get('/{id}/tutores', [TutorController::class, 'index'])->middleware('permiso-patrocinados:patrocinados.ver');
    Route::post('/{id}/tutores', [TutorController::class, 'store'])->middleware('permiso-patrocinados:patrocinados.editar');
});

Route::prefix('tutores')->group(function () {
    Route::put('/{id}', [TutorController::class, 'update'])->middleware('permiso-patrocinados:patrocinados.editar');
    Route::delete('/{id}', [TutorController::class, 'destroy'])->middleware('permiso-patrocinados:patrocinados.eliminar');
});

Route::apiResource('estados-patrocinados', EstadoPatrocinadoController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('permiso-patrocinados:patrocinados.ver|crear|editar|eliminar');

Route::apiResource('tipos-parentescos', TipoParentescoController::class)
    ->only(['index', 'store', 'update', 'destroy'])
    ->middleware('permiso-patrocinados:patrocinados.ver|crear|editar|eliminar');
```

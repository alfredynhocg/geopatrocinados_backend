# Código completo — Etapa 3: Geografía

> Fuente de columnas: migraciones reales en `database/migrations/patrocinados/2026_09_01_000010..000013_*`.
> Patrón de referencia: sección "Patrón completo: ejemplo con Noticias" de `CLAUDE.md`.
> Conexión: todo modelo usa el trait `UsaConexionPatrocinados` (ver `docs/patrocinados/codigo/01-infraestructura-base-codigo.md`).

## Domain/Geografia

#### app/Domain/Geografia/Contracts/DepartamentoRepositoryInterface.php

```php
<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface DepartamentoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Geografia/Contracts/MunicipioRepositoryInterface.php

```php
<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface MunicipioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $departamentoId = null): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Geografia/Contracts/ComunidadRepositoryInterface.php

```php
<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface ComunidadRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $municipioId = null): array;

    public function findById(string $id): mixed;

    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Geografia/Contracts/UbicacionRepositoryInterface.php

```php
<?php

namespace App\Domain\Geografia\Contracts;

use App\Shared\Kernel\DTOs\PaginationDTO;

interface UbicacionRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $comunidadId = null): array;

    public function findById(string $id): mixed;

    /**
     * `data` debe incluir `latitude`/`longitude`; el Repository recalcula
     * `punto_geografico` en el mismo insert — nunca se acepta el punto crudo.
     */
    public function create(array $data): mixed;

    public function update(string $id, array $data): mixed;

    public function delete(string|array $ids): bool;
}
```

#### app/Domain/Geografia/Exceptions/DepartamentoNotFoundException.php

```php
<?php

namespace App\Domain\Geografia\Exceptions;

class DepartamentoNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Departamento '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/Geografia/Exceptions/MunicipioNotFoundException.php

```php
<?php

namespace App\Domain\Geografia\Exceptions;

class MunicipioNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Municipio '{$id}' no encontrado.", 404);
    }
}
```

#### app/Domain/Geografia/Exceptions/ComunidadNotFoundException.php

```php
<?php

namespace App\Domain\Geografia\Exceptions;

class ComunidadNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Comunidad '{$id}' no encontrada.", 404);
    }
}
```

#### app/Domain/Geografia/Exceptions/UbicacionNotFoundException.php

```php
<?php

namespace App\Domain\Geografia\Exceptions;

class UbicacionNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct("Ubicación '{$id}' no encontrada.", 404);
    }
}
```

## Application/Geografia

### DTOs

#### app/Application/Geografia/DTOs/DepartamentoDTO.php

```php
<?php

namespace App\Application\Geografia\DTOs;

final readonly class DepartamentoDTO
{
    public function __construct(
        public string $id,
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
        public ?string $updated_by,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            codigo: $model->codigo,
            departamento: $model->departamento,
            estado: (bool) $model->estado,
            updated_by: $model->updated_by,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Geografia/DTOs/MunicipioDTO.php

```php
<?php

namespace App\Application\Geografia\DTOs;

final readonly class MunicipioDTO
{
    public function __construct(
        public string $id,
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
        public ?string $updated_by,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            departamento_id: $model->departamento_id,
            codigo: $model->codigo,
            municipio: $model->municipio,
            estado: (bool) $model->estado,
            updated_by: $model->updated_by,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Geografia/DTOs/ComunidadDTO.php

```php
<?php

namespace App\Application\Geografia\DTOs;

final readonly class ComunidadDTO
{
    public function __construct(
        public string $id,
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
        public ?string $updated_by,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            municipio_id: $model->municipio_id,
            codigo: $model->codigo,
            comunidad: $model->comunidad,
            estado: (bool) $model->estado,
            updated_by: $model->updated_by,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

#### app/Application/Geografia/DTOs/UbicacionDTO.php

```php
<?php

namespace App\Application\Geografia\DTOs;

final readonly class UbicacionDTO
{
    public function __construct(
        public string $id,
        public string $comunidad_id,
        public string $nombre,
        public ?string $tipo,
        public ?string $direccion,
        public ?float $latitude,
        public ?float $longitude,
        public ?float $precision_metros,
        public bool $estado,
        public ?string $updated_by,
        public ?string $created_at,
        public ?string $updated_at,
    ) {}

    /**
     * $model debe traer latitude/longitude como columnas planas ya resueltas
     * por el Repository (ver EloquentUbicacionRepository) — este DTO nunca
     * lee ni expone `punto_geografico` crudo.
     */
    public static function fromModel(object $model): self
    {
        return new self(
            id: $model->id,
            comunidad_id: $model->comunidad_id,
            nombre: $model->nombre,
            tipo: $model->tipo,
            direccion: $model->direccion,
            latitude: $model->latitude !== null ? (float) $model->latitude : null,
            longitude: $model->longitude !== null ? (float) $model->longitude : null,
            precision_metros: $model->precision_metros !== null ? (float) $model->precision_metros : null,
            estado: (bool) $model->estado,
            updated_by: $model->updated_by,
            created_at: $model->created_at?->toIso8601String(),
            updated_at: $model->updated_at?->toIso8601String(),
        );
    }
}
```

### Commands

#### app/Application/Geografia/Commands/CreateDepartamentoCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateDepartamentoCommand
{
    public function __construct(
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/UpdateDepartamentoCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateDepartamentoCommand
{
    public function __construct(
        public string $id,
        public ?string $codigo,
        public string $departamento,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/DeleteDepartamentoCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteDepartamentoCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Commands/CreateMunicipioCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateMunicipioCommand
{
    public function __construct(
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/UpdateMunicipioCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateMunicipioCommand
{
    public function __construct(
        public string $id,
        public string $departamento_id,
        public ?string $codigo,
        public string $municipio,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/DeleteMunicipioCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteMunicipioCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Commands/CreateComunidadCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateComunidadCommand
{
    public function __construct(
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/UpdateComunidadCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateComunidadCommand
{
    public function __construct(
        public string $id,
        public string $municipio_id,
        public ?string $codigo,
        public string $comunidad,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/DeleteComunidadCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteComunidadCommand
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Commands/CreateUbicacionCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class CreateUbicacionCommand
{
    public function __construct(
        public string $comunidad_id,
        public string $nombre,
        public ?string $tipo,
        public ?string $direccion,
        public float $latitude,
        public float $longitude,
        public ?float $precision_metros,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/UpdateUbicacionCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class UpdateUbicacionCommand
{
    public function __construct(
        public string $id,
        public string $comunidad_id,
        public string $nombre,
        public ?string $tipo,
        public ?string $direccion,
        public float $latitude,
        public float $longitude,
        public ?float $precision_metros,
        public bool $estado,
        public ?string $updated_by,
    ) {}
}
```

#### app/Application/Geografia/Commands/DeleteUbicacionCommand.php

```php
<?php

namespace App\Application\Geografia\Commands;

final readonly class DeleteUbicacionCommand
{
    public function __construct(public string $id) {}
}
```

### Handlers

#### app/Application/Geografia/Handlers/CreateDepartamentoHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateDepartamentoCommand;
use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class CreateDepartamentoHandler
{
    public function __construct(
        private readonly DepartamentoRepositoryInterface $repository
    ) {}

    public function handle(CreateDepartamentoCommand $command): DepartamentoDTO
    {
        $model = $this->repository->create([
            'codigo' => $command->codigo,
            'departamento' => $command->departamento,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return DepartamentoDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/UpdateDepartamentoHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateDepartamentoCommand;
use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;
use App\Domain\Geografia\Exceptions\DepartamentoNotFoundException;

class UpdateDepartamentoHandler
{
    public function __construct(
        private readonly DepartamentoRepositoryInterface $repository
    ) {}

    public function handle(UpdateDepartamentoCommand $command): DepartamentoDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new DepartamentoNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'codigo' => $command->codigo,
            'departamento' => $command->departamento,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return DepartamentoDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/DeleteDepartamentoHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteDepartamentoCommand;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class DeleteDepartamentoHandler
{
    public function __construct(
        private readonly DepartamentoRepositoryInterface $repository
    ) {}

    public function handle(DeleteDepartamentoCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/Geografia/Handlers/CreateMunicipioHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateMunicipioCommand;
use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class CreateMunicipioHandler
{
    public function __construct(
        private readonly MunicipioRepositoryInterface $repository
    ) {}

    public function handle(CreateMunicipioCommand $command): MunicipioDTO
    {
        $model = $this->repository->create([
            'departamento_id' => $command->departamento_id,
            'codigo' => $command->codigo,
            'municipio' => $command->municipio,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return MunicipioDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/UpdateMunicipioHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateMunicipioCommand;
use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;
use App\Domain\Geografia\Exceptions\MunicipioNotFoundException;

class UpdateMunicipioHandler
{
    public function __construct(
        private readonly MunicipioRepositoryInterface $repository
    ) {}

    public function handle(UpdateMunicipioCommand $command): MunicipioDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new MunicipioNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'departamento_id' => $command->departamento_id,
            'codigo' => $command->codigo,
            'municipio' => $command->municipio,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return MunicipioDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/DeleteMunicipioHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteMunicipioCommand;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class DeleteMunicipioHandler
{
    public function __construct(
        private readonly MunicipioRepositoryInterface $repository
    ) {}

    public function handle(DeleteMunicipioCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/Geografia/Handlers/CreateComunidadHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateComunidadCommand;
use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class CreateComunidadHandler
{
    public function __construct(
        private readonly ComunidadRepositoryInterface $repository
    ) {}

    public function handle(CreateComunidadCommand $command): ComunidadDTO
    {
        $model = $this->repository->create([
            'municipio_id' => $command->municipio_id,
            'codigo' => $command->codigo,
            'comunidad' => $command->comunidad,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return ComunidadDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/UpdateComunidadHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateComunidadCommand;
use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;
use App\Domain\Geografia\Exceptions\ComunidadNotFoundException;

class UpdateComunidadHandler
{
    public function __construct(
        private readonly ComunidadRepositoryInterface $repository
    ) {}

    public function handle(UpdateComunidadCommand $command): ComunidadDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new ComunidadNotFoundException($command->id);
        }

        $model = $this->repository->update($command->id, [
            'municipio_id' => $command->municipio_id,
            'codigo' => $command->codigo,
            'comunidad' => $command->comunidad,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return ComunidadDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/DeleteComunidadHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteComunidadCommand;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class DeleteComunidadHandler
{
    public function __construct(
        private readonly ComunidadRepositoryInterface $repository
    ) {}

    public function handle(DeleteComunidadCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

#### app/Application/Geografia/Handlers/CreateUbicacionHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateUbicacionCommand;
use App\Application\Geografia\DTOs\UbicacionDTO;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class CreateUbicacionHandler
{
    public function __construct(
        private readonly UbicacionRepositoryInterface $repository
    ) {}

    public function handle(CreateUbicacionCommand $command): UbicacionDTO
    {
        // El Repository recalcula punto_geografico a partir de latitude/longitude —
        // este Handler nunca construye el punto PostGIS directamente.
        $model = $this->repository->create([
            'comunidad_id' => $command->comunidad_id,
            'nombre' => $command->nombre,
            'tipo' => $command->tipo,
            'direccion' => $command->direccion,
            'latitude' => $command->latitude,
            'longitude' => $command->longitude,
            'precision_metros' => $command->precision_metros,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return UbicacionDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/UpdateUbicacionHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateUbicacionCommand;
use App\Application\Geografia\DTOs\UbicacionDTO;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;
use App\Domain\Geografia\Exceptions\UbicacionNotFoundException;

class UpdateUbicacionHandler
{
    public function __construct(
        private readonly UbicacionRepositoryInterface $repository
    ) {}

    public function handle(UpdateUbicacionCommand $command): UbicacionDTO
    {
        if ($this->repository->findById($command->id) === null) {
            throw new UbicacionNotFoundException($command->id);
        }

        // latitude/longitude siempre van juntos: no existe un update parcial
        // de solo uno de los dos sin recomputar punto_geografico (regla de
        // sincronía de la Etapa 3).
        $model = $this->repository->update($command->id, [
            'comunidad_id' => $command->comunidad_id,
            'nombre' => $command->nombre,
            'tipo' => $command->tipo,
            'direccion' => $command->direccion,
            'latitude' => $command->latitude,
            'longitude' => $command->longitude,
            'precision_metros' => $command->precision_metros,
            'estado' => $command->estado,
            'updated_by' => $command->updated_by,
        ]);

        return UbicacionDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/Handlers/DeleteUbicacionHandler.php

```php
<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\DeleteUbicacionCommand;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class DeleteUbicacionHandler
{
    public function __construct(
        private readonly UbicacionRepositoryInterface $repository
    ) {}

    public function handle(DeleteUbicacionCommand $command): bool
    {
        return $this->repository->delete($command->id);
    }
}
```

### Queries

#### app/Application/Geografia/Queries/GetDepartamentosQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetDepartamentosQuery
{
    public function __construct(public PaginationDTO $pagination) {}
}
```

#### app/Application/Geografia/Queries/GetDepartamentoByIdQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

final readonly class GetDepartamentoByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Queries/GetMunicipiosQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetMunicipiosQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $departamentoId = null,
    ) {}
}
```

#### app/Application/Geografia/Queries/GetMunicipioByIdQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

final readonly class GetMunicipioByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Queries/GetComunidadesQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetComunidadesQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $municipioId = null,
    ) {}
}
```

#### app/Application/Geografia/Queries/GetComunidadByIdQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

final readonly class GetComunidadByIdQuery
{
    public function __construct(public string $id) {}
}
```

#### app/Application/Geografia/Queries/GetUbicacionesQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

use App\Shared\Kernel\DTOs\PaginationDTO;

final readonly class GetUbicacionesQuery
{
    public function __construct(
        public PaginationDTO $pagination,
        public ?string $comunidadId = null,
    ) {}
}
```

#### app/Application/Geografia/Queries/GetUbicacionByIdQuery.php

```php
<?php

namespace App\Application\Geografia\Queries;

final readonly class GetUbicacionByIdQuery
{
    public function __construct(public string $id) {}
}
```

### QueryHandlers

#### app/Application/Geografia/QueryHandlers/GetDepartamentosQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\Queries\GetDepartamentosQuery;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class GetDepartamentosQueryHandler
{
    public function __construct(
        private readonly DepartamentoRepositoryInterface $repository
    ) {}

    public function handle(GetDepartamentosQuery $query): array
    {
        return $this->repository->paginate($query->pagination);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetDepartamentoByIdQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Application\Geografia\Queries\GetDepartamentoByIdQuery;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;
use App\Domain\Geografia\Exceptions\DepartamentoNotFoundException;

class GetDepartamentoByIdQueryHandler
{
    public function __construct(
        private readonly DepartamentoRepositoryInterface $repository
    ) {}

    public function handle(GetDepartamentoByIdQuery $query): DepartamentoDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new DepartamentoNotFoundException($query->id);
        }

        return DepartamentoDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetMunicipiosQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\Queries\GetMunicipiosQuery;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class GetMunicipiosQueryHandler
{
    public function __construct(
        private readonly MunicipioRepositoryInterface $repository
    ) {}

    public function handle(GetMunicipiosQuery $query): array
    {
        return $this->repository->paginate($query->pagination, $query->departamentoId);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetMunicipioByIdQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Application\Geografia\Queries\GetMunicipioByIdQuery;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;
use App\Domain\Geografia\Exceptions\MunicipioNotFoundException;

class GetMunicipioByIdQueryHandler
{
    public function __construct(
        private readonly MunicipioRepositoryInterface $repository
    ) {}

    public function handle(GetMunicipioByIdQuery $query): MunicipioDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new MunicipioNotFoundException($query->id);
        }

        return MunicipioDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetComunidadesQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\Queries\GetComunidadesQuery;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class GetComunidadesQueryHandler
{
    public function __construct(
        private readonly ComunidadRepositoryInterface $repository
    ) {}

    public function handle(GetComunidadesQuery $query): array
    {
        return $this->repository->paginate($query->pagination, $query->municipioId);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetComunidadByIdQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Application\Geografia\Queries\GetComunidadByIdQuery;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;
use App\Domain\Geografia\Exceptions\ComunidadNotFoundException;

class GetComunidadByIdQueryHandler
{
    public function __construct(
        private readonly ComunidadRepositoryInterface $repository
    ) {}

    public function handle(GetComunidadByIdQuery $query): ComunidadDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new ComunidadNotFoundException($query->id);
        }

        return ComunidadDTO::fromModel($model);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetUbicacionesQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\Queries\GetUbicacionesQuery;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class GetUbicacionesQueryHandler
{
    public function __construct(
        private readonly UbicacionRepositoryInterface $repository
    ) {}

    public function handle(GetUbicacionesQuery $query): array
    {
        return $this->repository->paginate($query->pagination, $query->comunidadId);
    }
}
```

#### app/Application/Geografia/QueryHandlers/GetUbicacionByIdQueryHandler.php

```php
<?php

namespace App\Application\Geografia\QueryHandlers;

use App\Application\Geografia\DTOs\UbicacionDTO;
use App\Application\Geografia\Queries\GetUbicacionByIdQuery;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;
use App\Domain\Geografia\Exceptions\UbicacionNotFoundException;

class GetUbicacionByIdQueryHandler
{
    public function __construct(
        private readonly UbicacionRepositoryInterface $repository
    ) {}

    public function handle(GetUbicacionByIdQuery $query): UbicacionDTO
    {
        $model = $this->repository->findById($query->id);
        if ($model === null) {
            throw new UbicacionNotFoundException($query->id);
        }

        return UbicacionDTO::fromModel($model);
    }
}
```

## Infrastructure/Geografia

### Models

#### app/Infrastructure/Geografia/Models/Departamento.php

```php
<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'departamento';

    protected $fillable = [
        'codigo',
        'departamento',
        'estado',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function municipios(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Municipio::class, 'departamento_id');
    }
}
```

#### app/Infrastructure/Geografia/Models/Municipio.php

```php
<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'municipios';

    protected $fillable = [
        'departamento_id',
        'codigo',
        'municipio',
        'estado',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function departamento(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function comunidades(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comunidad::class, 'municipio_id');
    }
}
```

#### app/Infrastructure/Geografia/Models/Comunidad.php

```php
<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Comunidad extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'comunidades';

    protected $fillable = [
        'municipio_id',
        'codigo',
        'comunidad',
        'estado',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
    ];

    public function municipio(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function ubicaciones(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ubicacion::class, 'comunidad_id');
    }
}
```

#### app/Infrastructure/Geografia/Models/Ubicacion.php

```php
<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Ubicacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'ubicaciones';

    // `punto_geografico` deliberadamente NO está en $fillable: solo el
    // EloquentUbicacionRepository lo escribe, vía SQL crudo, a partir de
    // latitude/longitude. Nunca se asigna por mass-assignment.
    protected $fillable = [
        'comunidad_id',
        'nombre',
        'tipo',
        'direccion',
        'latitude',
        'longitude',
        'precision_metros',
        'estado',
        'updated_by',
    ];

    protected $casts = [
        'estado' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'precision_metros' => 'decimal:2',
    ];

    public function comunidad(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }
}
```

### Repositories

#### app/Infrastructure/Geografia/Repositories/EloquentDepartamentoRepository.php

```php
<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;
use App\Domain\Geografia\Exceptions\DepartamentoNotFoundException;
use App\Infrastructure\Geografia\Models\Departamento;
use App\Shared\Kernel\DTOs\PaginationDTO;
use App\Application\Geografia\DTOs\DepartamentoDTO;

class EloquentDepartamentoRepository implements DepartamentoRepositoryInterface
{
    public function paginate(PaginationDTO $pagination): array
    {
        $q = Departamento::query();

        if ($pagination->query !== '') {
            $q->where('departamento', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'departamento', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => DepartamentoDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Departamento
    {
        return Departamento::find($id);
    }

    public function create(array $data): Departamento
    {
        return Departamento::create($data);
    }

    public function update(string $id, array $data): Departamento
    {
        $model = Departamento::find($id);
        if ($model === null) {
            throw new DepartamentoNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Departamento::destroy($ids);
    }
}
```

#### app/Infrastructure/Geografia/Repositories/EloquentMunicipioRepository.php

```php
<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;
use App\Domain\Geografia\Exceptions\MunicipioNotFoundException;
use App\Infrastructure\Geografia\Models\Municipio;
use App\Shared\Kernel\DTOs\PaginationDTO;
use App\Application\Geografia\DTOs\MunicipioDTO;

class EloquentMunicipioRepository implements MunicipioRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $departamentoId = null): array
    {
        $q = Municipio::query();

        if ($departamentoId !== null) {
            $q->where('departamento_id', $departamentoId);
        }

        if ($pagination->query !== '') {
            $q->where('municipio', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'municipio', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => MunicipioDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Municipio
    {
        return Municipio::find($id);
    }

    public function create(array $data): Municipio
    {
        return Municipio::create($data);
    }

    public function update(string $id, array $data): Municipio
    {
        $model = Municipio::find($id);
        if ($model === null) {
            throw new MunicipioNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Municipio::destroy($ids);
    }
}
```

#### app/Infrastructure/Geografia/Repositories/EloquentComunidadRepository.php

```php
<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;
use App\Domain\Geografia\Exceptions\ComunidadNotFoundException;
use App\Infrastructure\Geografia\Models\Comunidad;
use App\Shared\Kernel\DTOs\PaginationDTO;
use App\Application\Geografia\DTOs\ComunidadDTO;

class EloquentComunidadRepository implements ComunidadRepositoryInterface
{
    public function paginate(PaginationDTO $pagination, ?string $municipioId = null): array
    {
        $q = Comunidad::query();

        if ($municipioId !== null) {
            $q->where('municipio_id', $municipioId);
        }

        if ($pagination->query !== '') {
            $q->where('comunidad', 'ilike', "%{$pagination->query}%");
        }

        $paginated = $q->orderBy($pagination->sortKey ?: 'comunidad', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => ComunidadDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Comunidad
    {
        return Comunidad::find($id);
    }

    public function create(array $data): Comunidad
    {
        return Comunidad::create($data);
    }

    public function update(string $id, array $data): Comunidad
    {
        $model = Comunidad::find($id);
        if ($model === null) {
            throw new ComunidadNotFoundException($id);
        }

        $model->update($data);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Comunidad::destroy($ids);
    }
}
```

#### app/Infrastructure/Geografia/Repositories/EloquentUbicacionRepository.php

```php
<?php

namespace App\Infrastructure\Geografia\Repositories;

use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;
use App\Domain\Geografia\Exceptions\UbicacionNotFoundException;
use App\Infrastructure\Geografia\Models\Ubicacion;
use App\Shared\Kernel\DTOs\PaginationDTO;
use App\Application\Geografia\DTOs\UbicacionDTO;
use Illuminate\Support\Facades\DB;

class EloquentUbicacionRepository implements UbicacionRepositoryInterface
{
    private const CONNECTION = 'pgsql_patrocinados';

    public function paginate(PaginationDTO $pagination, ?string $comunidadId = null): array
    {
        $q = Ubicacion::query();

        if ($comunidadId !== null) {
            $q->where('comunidad_id', $comunidadId);
        }

        if ($pagination->query !== '') {
            $q->where('nombre', 'ilike', "%{$pagination->query}%");
        }

        // latitude/longitude ya son columnas planas propias de la tabla —
        // no hace falta ST_X/ST_Y aquí, solo al leer directamente punto_geografico.
        $paginated = $q->orderBy($pagination->sortKey ?: 'nombre', $pagination->sortOrder)
            ->paginate($pagination->pageSize, ['*'], 'page', $pagination->pageIndex);

        return [
            'data' => collect($paginated->items())->map(fn ($m) => UbicacionDTO::fromModel($m))->all(),
            'total' => $paginated->total(),
        ];
    }

    public function findById(string $id): ?Ubicacion
    {
        return Ubicacion::find($id);
    }

    public function create(array $data): Ubicacion
    {
        $model = Ubicacion::create($data);
        $this->recalcularPuntoGeografico($model->id, $data['longitude'], $data['latitude']);

        return $model->refresh();
    }

    public function update(string $id, array $data): Ubicacion
    {
        $model = Ubicacion::find($id);
        if ($model === null) {
            throw new UbicacionNotFoundException($id);
        }

        $model->update($data);
        $this->recalcularPuntoGeografico($id, $data['longitude'], $data['latitude']);

        return $model->refresh();
    }

    public function delete(string|array $ids): bool
    {
        return (bool) Ubicacion::destroy($ids);
    }

    /**
     * Única fuente de verdad de la derivación lat/lng -> GEOGRAPHY (Opción A,
     * sin librería adicional): reconstruye punto_geografico en cada
     * create/update a partir de latitude/longitude recién guardados.
     * Orden de ST_MakePoint: (longitude, latitude) — no al revés.
     */
    private function recalcularPuntoGeografico(string $id, float $longitude, float $latitude): void
    {
        DB::connection(self::CONNECTION)->statement(
            'UPDATE ubicaciones SET punto_geografico = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography WHERE id = ?',
            [$longitude, $latitude, $id]
        );
    }
}
```

## Http

### Controllers

#### app/Http/Controllers/Api/Patrocinados/DepartamentoController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateDepartamentoCommand;
use App\Application\Geografia\Commands\DeleteDepartamentoCommand;
use App\Application\Geografia\Commands\UpdateDepartamentoCommand;
use App\Application\Geografia\Handlers\CreateDepartamentoHandler;
use App\Application\Geografia\Handlers\DeleteDepartamentoHandler;
use App\Application\Geografia\Handlers\UpdateDepartamentoHandler;
use App\Application\Geografia\Queries\GetDepartamentoByIdQuery;
use App\Application\Geografia\Queries\GetDepartamentosQuery;
use App\Application\Geografia\QueryHandlers\GetDepartamentoByIdQueryHandler;
use App\Application\Geografia\QueryHandlers\GetDepartamentosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreDepartamentoRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateDepartamentoRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartamentoController extends Controller
{
    public function __construct(
        private readonly GetDepartamentosQueryHandler $getDepartamentosHandler,
        private readonly GetDepartamentoByIdQueryHandler $getDepartamentoByIdHandler,
        private readonly CreateDepartamentoHandler $createHandler,
        private readonly UpdateDepartamentoHandler $updateHandler,
        private readonly DeleteDepartamentoHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all(), 'departamento');

        return response()->json(
            $this->getDepartamentosHandler->handle(new GetDepartamentosQuery($pagination))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getDepartamentoByIdHandler->handle(new GetDepartamentoByIdQuery($id))
        );
    }

    public function store(StoreDepartamentoRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateDepartamentoCommand(
            codigo: $request->codigo,
            departamento: $request->departamento,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateDepartamentoRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateDepartamentoCommand(
            id: $id,
            codigo: $request->codigo,
            departamento: $request->departamento,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteDepartamentoCommand($id));

        return response()->json(null, 204);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/MunicipioController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateMunicipioCommand;
use App\Application\Geografia\Commands\DeleteMunicipioCommand;
use App\Application\Geografia\Commands\UpdateMunicipioCommand;
use App\Application\Geografia\Handlers\CreateMunicipioHandler;
use App\Application\Geografia\Handlers\DeleteMunicipioHandler;
use App\Application\Geografia\Handlers\UpdateMunicipioHandler;
use App\Application\Geografia\Queries\GetMunicipioByIdQuery;
use App\Application\Geografia\Queries\GetMunicipiosQuery;
use App\Application\Geografia\QueryHandlers\GetMunicipioByIdQueryHandler;
use App\Application\Geografia\QueryHandlers\GetMunicipiosQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreMunicipioRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateMunicipioRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    public function __construct(
        private readonly GetMunicipiosQueryHandler $getMunicipiosHandler,
        private readonly GetMunicipioByIdQueryHandler $getMunicipioByIdHandler,
        private readonly CreateMunicipioHandler $createHandler,
        private readonly UpdateMunicipioHandler $updateHandler,
        private readonly DeleteMunicipioHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all(), 'municipio');

        return response()->json(
            $this->getMunicipiosHandler->handle(new GetMunicipiosQuery(
                $pagination,
                $request->string('departamento_id')->toString() ?: null,
            ))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getMunicipioByIdHandler->handle(new GetMunicipioByIdQuery($id))
        );
    }

    public function store(StoreMunicipioRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateMunicipioCommand(
            departamento_id: $request->departamento_id,
            codigo: $request->codigo,
            municipio: $request->municipio,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateMunicipioRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateMunicipioCommand(
            id: $id,
            departamento_id: $request->departamento_id,
            codigo: $request->codigo,
            municipio: $request->municipio,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteMunicipioCommand($id));

        return response()->json(null, 204);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/ComunidadController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateComunidadCommand;
use App\Application\Geografia\Commands\DeleteComunidadCommand;
use App\Application\Geografia\Commands\UpdateComunidadCommand;
use App\Application\Geografia\Handlers\CreateComunidadHandler;
use App\Application\Geografia\Handlers\DeleteComunidadHandler;
use App\Application\Geografia\Handlers\UpdateComunidadHandler;
use App\Application\Geografia\Queries\GetComunidadByIdQuery;
use App\Application\Geografia\Queries\GetComunidadesQuery;
use App\Application\Geografia\QueryHandlers\GetComunidadByIdQueryHandler;
use App\Application\Geografia\QueryHandlers\GetComunidadesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreComunidadRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateComunidadRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComunidadController extends Controller
{
    public function __construct(
        private readonly GetComunidadesQueryHandler $getComunidadesHandler,
        private readonly GetComunidadByIdQueryHandler $getComunidadByIdHandler,
        private readonly CreateComunidadHandler $createHandler,
        private readonly UpdateComunidadHandler $updateHandler,
        private readonly DeleteComunidadHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all(), 'comunidad');

        return response()->json(
            $this->getComunidadesHandler->handle(new GetComunidadesQuery(
                $pagination,
                $request->string('municipio_id')->toString() ?: null,
            ))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getComunidadByIdHandler->handle(new GetComunidadByIdQuery($id))
        );
    }

    public function store(StoreComunidadRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateComunidadCommand(
            municipio_id: $request->municipio_id,
            codigo: $request->codigo,
            comunidad: $request->comunidad,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateComunidadRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateComunidadCommand(
            id: $id,
            municipio_id: $request->municipio_id,
            codigo: $request->codigo,
            comunidad: $request->comunidad,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteComunidadCommand($id));

        return response()->json(null, 204);
    }
}
```

#### app/Http/Controllers/Api/Patrocinados/UbicacionController.php

```php
<?php

namespace App\Http\Controllers\Api\Patrocinados;

use App\Application\Geografia\Commands\CreateUbicacionCommand;
use App\Application\Geografia\Commands\DeleteUbicacionCommand;
use App\Application\Geografia\Commands\UpdateUbicacionCommand;
use App\Application\Geografia\Handlers\CreateUbicacionHandler;
use App\Application\Geografia\Handlers\DeleteUbicacionHandler;
use App\Application\Geografia\Handlers\UpdateUbicacionHandler;
use App\Application\Geografia\Queries\GetUbicacionByIdQuery;
use App\Application\Geografia\Queries\GetUbicacionesQuery;
use App\Application\Geografia\QueryHandlers\GetUbicacionByIdQueryHandler;
use App\Application\Geografia\QueryHandlers\GetUbicacionesQueryHandler;
use App\Http\Controllers\Controller;
use App\Http\Requests\Patrocinados\Geografia\StoreUbicacionRequest;
use App\Http\Requests\Patrocinados\Geografia\UpdateUbicacionRequest;
use App\Shared\Kernel\DTOs\PaginationDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UbicacionController extends Controller
{
    public function __construct(
        private readonly GetUbicacionesQueryHandler $getUbicacionesHandler,
        private readonly GetUbicacionByIdQueryHandler $getUbicacionByIdHandler,
        private readonly CreateUbicacionHandler $createHandler,
        private readonly UpdateUbicacionHandler $updateHandler,
        private readonly DeleteUbicacionHandler $deleteHandler,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $pagination = PaginationDTO::fromArray($request->all(), 'nombre');

        return response()->json(
            $this->getUbicacionesHandler->handle(new GetUbicacionesQuery(
                $pagination,
                $request->string('comunidad_id')->toString() ?: null,
            ))
        );
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            $this->getUbicacionByIdHandler->handle(new GetUbicacionByIdQuery($id))
        );
    }

    public function store(StoreUbicacionRequest $request): JsonResponse
    {
        $dto = $this->createHandler->handle(new CreateUbicacionCommand(
            comunidad_id: $request->comunidad_id,
            nombre: $request->nombre,
            tipo: $request->tipo,
            direccion: $request->direccion,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precision_metros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto, 201);
    }

    public function update(UpdateUbicacionRequest $request, string $id): JsonResponse
    {
        $dto = $this->updateHandler->handle(new UpdateUbicacionCommand(
            id: $id,
            comunidad_id: $request->comunidad_id,
            nombre: $request->nombre,
            tipo: $request->tipo,
            direccion: $request->direccion,
            latitude: (float) $request->latitude,
            longitude: (float) $request->longitude,
            precision_metros: $request->precision_metros !== null ? (float) $request->precision_metros : null,
            estado: $request->boolean('estado', true),
            updated_by: auth()->id(),
        ));

        return response()->json($dto);
    }

    public function destroy(string $id): JsonResponse
    {
        $this->deleteHandler->handle(new DeleteUbicacionCommand($id));

        return response()->json(null, 204);
    }
}
```

### Requests

#### app/Http/Requests/Patrocinados/Geografia/StoreDepartamentoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => ['nullable', 'string', 'max:30', 'unique:pgsql_patrocinados.departamento,codigo'],
            'departamento' => ['required', 'string', 'max:150'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/UpdateDepartamentoRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('pgsql_patrocinados.departamento', 'codigo')->ignore($this->route('id')),
            ],
            'departamento' => ['required', 'string', 'max:150'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/StoreMunicipioRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.departamento,id'],
            'codigo' => ['nullable', 'string', 'max:30', 'unique:pgsql_patrocinados.municipios,codigo'],
            'municipio' => ['required', 'string', 'max:150'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/UpdateMunicipioRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.departamento,id'],
            'codigo' => [
                'nullable', 'string', 'max:30',
                Rule::unique('pgsql_patrocinados.municipios', 'codigo')->ignore($this->route('id')),
            ],
            'municipio' => ['required', 'string', 'max:150'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/StoreComunidadRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.municipios,id'],
            // `codigo` no es UNIQUE en esta tabla (confirmado en la migración real).
            'codigo' => ['nullable', 'string', 'max:30'],
            'comunidad' => ['required', 'string', 'max:180'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/UpdateComunidadRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComunidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.municipios,id'],
            'codigo' => ['nullable', 'string', 'max:30'],
            'comunidad' => ['required', 'string', 'max:180'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/StoreUbicacionRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comunidad_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'nombre' => ['required', 'string', 'max:180'],
            'tipo' => ['nullable', Rule::in(['DOMICILIO', 'ESCUELA', 'PUNTO_REFERENCIA', 'OTRO'])],
            'direccion' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

#### app/Http/Requests/Patrocinados/Geografia/UpdateUbicacionRequest.php

```php
<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comunidad_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'nombre' => ['required', 'string', 'max:180'],
            'tipo' => ['nullable', Rule::in(['DOMICILIO', 'ESCUELA', 'PUNTO_REFERENCIA', 'OTRO'])],
            'direccion' => ['nullable', 'string'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'boolean'],
        ];
    }
}
```

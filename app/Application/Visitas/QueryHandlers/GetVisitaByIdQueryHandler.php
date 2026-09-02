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

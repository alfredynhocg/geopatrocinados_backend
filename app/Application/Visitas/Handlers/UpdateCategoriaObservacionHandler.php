<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\UpdateCategoriaObservacionCommand;
use App\Application\Visitas\DTOs\CategoriaObservacionDTO;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;

class UpdateCategoriaObservacionHandler
{
    public function __construct(
        private readonly CategoriaObservacionRepositoryInterface $repository
    ) {}

    public function handle(UpdateCategoriaObservacionCommand $command): CategoriaObservacionDTO
    {
        $model = $this->repository->update($command->id, [
            'codigo'                  => $command->codigo,
            'categoria_observaciones' => $command->categoriaObservaciones,
            'descripcion'             => $command->descripcion,
            'estado'                  => $command->estado,
            'updated_by'              => $command->updatedBy,
        ]);

        return CategoriaObservacionDTO::fromModel($model);
    }
}

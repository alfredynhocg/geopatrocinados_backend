<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreateCategoriaObservacionCommand;
use App\Application\Visitas\DTOs\CategoriaObservacionDTO;
use App\Domain\Visitas\Contracts\CategoriaObservacionRepositoryInterface;

class CreateCategoriaObservacionHandler
{
    public function __construct(
        private readonly CategoriaObservacionRepositoryInterface $repository
    ) {}

    public function handle(CreateCategoriaObservacionCommand $command): CategoriaObservacionDTO
    {
        $model = $this->repository->create([
            'codigo'                  => $command->codigo,
            'categoria_observaciones' => $command->categoriaObservaciones,
            'descripcion'             => $command->descripcion,
            'estado'                  => true,
            'updated_by'              => $command->updatedBy,
        ]);

        return CategoriaObservacionDTO::fromModel($model);
    }
}

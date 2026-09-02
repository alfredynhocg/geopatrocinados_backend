<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateComunidadCommand;
use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class UpdateComunidadHandler
{
    public function __construct(private readonly ComunidadRepositoryInterface $repository) {}

    public function handle(UpdateComunidadCommand $command): ComunidadDTO
    {
        $model = $this->repository->update($command->id, [
            'municipio_id' => $command->municipio_id,
            'codigo'       => $command->codigo,
            'comunidad'    => $command->comunidad,
            'estado'       => $command->estado,
        ]);

        return ComunidadDTO::fromModel($model);
    }
}

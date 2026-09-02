<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateComunidadCommand;
use App\Application\Geografia\DTOs\ComunidadDTO;
use App\Domain\Geografia\Contracts\ComunidadRepositoryInterface;

class CreateComunidadHandler
{
    public function __construct(private readonly ComunidadRepositoryInterface $repository) {}

    public function handle(CreateComunidadCommand $command): ComunidadDTO
    {
        $model = $this->repository->create([
            'municipio_id' => $command->municipio_id,
            'codigo'       => $command->codigo,
            'comunidad'    => $command->comunidad,
            'estado'       => $command->estado,
        ]);

        return ComunidadDTO::fromModel($model);
    }
}

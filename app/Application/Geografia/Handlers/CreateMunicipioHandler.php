<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateMunicipioCommand;
use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class CreateMunicipioHandler
{
    public function __construct(private readonly MunicipioRepositoryInterface $repository) {}

    public function handle(CreateMunicipioCommand $command): MunicipioDTO
    {
        $model = $this->repository->create([
            'departamento_id' => $command->departamento_id,
            'codigo'          => $command->codigo,
            'municipio'       => $command->municipio,
            'estado'          => $command->estado,
        ]);

        return MunicipioDTO::fromModel($model);
    }
}

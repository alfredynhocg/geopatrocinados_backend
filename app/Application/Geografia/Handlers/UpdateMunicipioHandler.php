<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateMunicipioCommand;
use App\Application\Geografia\DTOs\MunicipioDTO;
use App\Domain\Geografia\Contracts\MunicipioRepositoryInterface;

class UpdateMunicipioHandler
{
    public function __construct(private readonly MunicipioRepositoryInterface $repository) {}

    public function handle(UpdateMunicipioCommand $command): MunicipioDTO
    {
        $model = $this->repository->update($command->id, [
            'departamento_id' => $command->departamento_id,
            'codigo'          => $command->codigo,
            'municipio'       => $command->municipio,
            'estado'          => $command->estado,
        ]);

        return MunicipioDTO::fromModel($model);
    }
}

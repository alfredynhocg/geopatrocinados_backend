<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateDepartamentoCommand;
use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class UpdateDepartamentoHandler
{
    public function __construct(private readonly DepartamentoRepositoryInterface $repository) {}

    public function handle(UpdateDepartamentoCommand $command): DepartamentoDTO
    {
        $model = $this->repository->update($command->id, [
            'codigo'       => $command->codigo,
            'departamento' => $command->departamento,
            'estado'       => $command->estado,
        ]);

        return DepartamentoDTO::fromModel($model);
    }
}

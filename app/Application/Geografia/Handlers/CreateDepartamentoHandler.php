<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\CreateDepartamentoCommand;
use App\Application\Geografia\DTOs\DepartamentoDTO;
use App\Domain\Geografia\Contracts\DepartamentoRepositoryInterface;

class CreateDepartamentoHandler
{
    public function __construct(private readonly DepartamentoRepositoryInterface $repository) {}

    public function handle(CreateDepartamentoCommand $command): DepartamentoDTO
    {
        $model = $this->repository->create([
            'codigo'       => $command->codigo,
            'departamento' => $command->departamento,
            'estado'       => $command->estado,
        ]);

        return DepartamentoDTO::fromModel($model);
    }
}

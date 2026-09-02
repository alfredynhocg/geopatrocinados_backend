<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\CreateTipoParentescoCommand;
use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class CreateTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(CreateTipoParentescoCommand $command): TipoParentescoDTO
    {
        $model = $this->repository->create([
            'parentesco' => $command->parentesco,
            'estado'     => $command->estado,
        ]);

        return TipoParentescoDTO::fromModel($model);
    }
}

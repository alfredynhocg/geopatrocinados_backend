<?php

namespace App\Application\Patrocinados\Handlers;

use App\Application\Patrocinados\Commands\UpdateTipoParentescoCommand;
use App\Application\Patrocinados\DTOs\TipoParentescoDTO;
use App\Domain\Patrocinados\Contracts\TipoParentescoRepositoryInterface;

class UpdateTipoParentescoHandler
{
    public function __construct(private readonly TipoParentescoRepositoryInterface $repository) {}

    public function handle(UpdateTipoParentescoCommand $command): TipoParentescoDTO
    {
        $model = $this->repository->update($command->id, [
            'parentesco' => $command->parentesco,
            'estado'     => $command->estado,
        ]);

        return TipoParentescoDTO::fromModel($model);
    }
}

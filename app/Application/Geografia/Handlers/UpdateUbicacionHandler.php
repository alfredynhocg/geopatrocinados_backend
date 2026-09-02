<?php

namespace App\Application\Geografia\Handlers;

use App\Application\Geografia\Commands\UpdateUbicacionCommand;
use App\Application\Geografia\DTOs\UbicacionDTO;
use App\Domain\Geografia\Contracts\UbicacionRepositoryInterface;

class UpdateUbicacionHandler
{
    public function __construct(private readonly UbicacionRepositoryInterface $repository) {}

    public function handle(UpdateUbicacionCommand $command): UbicacionDTO
    {
        $model = $this->repository->update($command->id, [
            'comunidad_id'      => $command->comunidad_id,
            'nombre'            => $command->nombre,
            'tipo'              => $command->tipo,
            'direccion'         => $command->direccion,
            'latitude'          => $command->latitude,
            'longitude'         => $command->longitude,
            'precision_metros'  => $command->precision_metros,
            'estado'            => $command->estado,
        ]);

        return UbicacionDTO::fromModel($model);
    }
}

<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\IniciarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\LoteSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class IniciarLoteSincronizacionHandler
{
    public function __construct(private readonly LoteSincronizacionRepositoryInterface $repository) {}

    public function handle(IniciarLoteSincronizacionCommand $command): LoteSincronizacionDTO
    {
        $model = $this->repository->create([
            'dispositivo_id' => $command->dispositivo_id,
            'user_id'        => $command->user_id,
            'fecha_inicio'   => now(),
            'estado'         => 'SINCRONIZANDO',
        ]);

        return LoteSincronizacionDTO::fromModel($model);
    }
}

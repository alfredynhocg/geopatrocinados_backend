<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CreateObservacionVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\ObservacionVisitaDTO;
use App\Domain\Visitas\Contracts\ObservacionVisitaRepositoryInterface;

class CreateObservacionVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly ObservacionVisitaRepositoryInterface $repository
    ) {}

    public function handle(CreateObservacionVisitaCommand $command): ObservacionVisitaDTO
    {
        $habilitacion = $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $model = $this->repository->create([
            'visita_id'   => $command->visitaId,
            'categoria_id'=> $command->categoriaId,
            'tipo'        => $command->tipo,
            'observacion' => $command->observacion,
            'created_by'  => $habilitacion->tecnico_id,
        ]);

        return ObservacionVisitaDTO::fromModel($model);
    }
}

<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\CapturarUbicacionVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\UbicacionVisitaDTO;
use App\Domain\Visitas\Contracts\UbicacionVisitaRepositoryInterface;

/**
 * El Repository (EloquentUbicacionVisitaRepository) es la única fuente de verdad
 * de la derivación lat/lng -> GEOGRAPHY, igual mecanismo que Geografia (Etapa 3, Opción A).
 */
class CapturarUbicacionVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly UbicacionVisitaRepositoryInterface $repository
    ) {}

    public function handle(CapturarUbicacionVisitaCommand $command): UbicacionVisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $model = $this->repository->create([
            'visita_id'         => $command->visitaId,
            'dispositivo_id'    => $command->dispositivoId,
            'tecnico_id'        => $command->tecnicoId,
            'fecha_captura'     => now(),
            'latitude'          => $command->latitude,
            'longitude'         => $command->longitude,
            'precision_metros'  => $command->precisionMetros,
            'fuente'            => $command->fuente,
        ]);

        return UbicacionVisitaDTO::fromModel($model);
    }
}

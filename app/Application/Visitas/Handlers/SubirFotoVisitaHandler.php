<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\SubirFotoVisitaCommand;
use App\Application\Visitas\Concerns\VerificaHabilitacionActiva;
use App\Application\Visitas\DTOs\FotoVisitaDTO;
use App\Application\Visitas\Services\FotoVisitaService;
use App\Domain\Visitas\Contracts\FotoVisitaRepositoryInterface;

class SubirFotoVisitaHandler
{
    use VerificaHabilitacionActiva;

    public function __construct(
        private readonly FotoVisitaRepositoryInterface $repository,
        private readonly FotoVisitaService $fotoService,
    ) {}

    public function handle(SubirFotoVisitaCommand $command): FotoVisitaDTO
    {
        $this->verificarHabilitacionActiva($command->visitaId, $command->dispositivoId);

        $almacenado = $this->fotoService->almacenar($command->archivo);

        $model = $this->repository->create([
            'visita_id'            => $command->visitaId,
            'clave_almacenamiento' => $almacenado->clave,
            'nombre_archivo'       => $command->archivo->getClientOriginalName(),
            'tipo_archivo'         => $command->archivo->getMimeType(),
            'tamanio'              => $command->archivo->getSize(),
            'hash_sha256'          => $almacenado->hashSha256,
            'fecha_captura'        => now(),
            'latitude'             => $command->latitude,
            'longitude'            => $command->longitude,
            'cifrada'              => $almacenado->cifrada,
        ]);

        return FotoVisitaDTO::fromModel($model, $this->fotoService->urlFirmada($model->clave_almacenamiento));
    }
}

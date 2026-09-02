<?php

namespace App\Application\Sincronizacion\Handlers;

use App\Application\Sincronizacion\Commands\CerrarLoteSincronizacionCommand;
use App\Application\Sincronizacion\DTOs\ResultadoSincronizacionDTO;
use App\Domain\Sincronizacion\Contracts\ElementoSincronizacionRepositoryInterface;
use App\Domain\Sincronizacion\Contracts\LoteSincronizacionRepositoryInterface;

class CerrarLoteSincronizacionHandler
{
    public function __construct(
        private readonly LoteSincronizacionRepositoryInterface $loteRepository,
        private readonly ElementoSincronizacionRepositoryInterface $elementoRepository,
    ) {}

    public function handle(CerrarLoteSincronizacionCommand $command): ResultadoSincronizacionDTO
    {
        $pendientes = $this->elementoRepository->listPendientesByLote($command->lote_id);
        $conError = collect($pendientes)->where('estado', 'ERROR')->count();

        $estadoFinal = $conError > 0 ? 'ERROR' : 'COMPLETADO';

        $lote = $this->loteRepository->cerrar(
            $command->lote_id,
            $command->registros_enviados,
            $command->registros_recibidos,
            $estadoFinal,
        );

        return new ResultadoSincronizacionDTO(
            lote_id: $lote->id,
            estado: $lote->estado,
            registros_enviados: $lote->registros_enviados,
            registros_recibidos: $lote->registros_recibidos,
            elementos_sincronizados: collect($pendientes)->where('estado', 'SINCRONIZADO')->count(),
            elementos_con_error: $conError,
        );
    }
}

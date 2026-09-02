<?php

namespace App\Application\Visitas\Handlers;

use App\Application\Visitas\Commands\DarDeBajaPatrocinadoPorNoUbicadoCommand;

/**
 * TODO: pendiente de coordinar con Etapa 5 (Patrocinados). Debe:
 *  1. Inyectar PatrocinadoRepositoryInterface (Domain/Patrocinados).
 *  2. Actualizar estados_patrocinados del niño a 'INACTIVO_NO_UBICADO' (código exacto
 *     a confirmar en la Etapa 5 — ver docs/patrocinados/05-patrocinados.md, decisión #2).
 *  3. Registrar en AuditoriaService (módulo 'Patrocinados', accion 'dar-de-baja').
 *  4. Marcar la visita asociada como CANCELADA vía VisitaRepositoryInterface::actualizarEstado().
 * No implementar el cambio de estado del patrocinado en este módulo (Visitas) para no
 * duplicar la fuente de verdad de estados_patrocinados fuera de Application/Patrocinados.
 */
class DarDeBajaPatrocinadoPorNoUbicadoHandler
{
    public function handle(DarDeBajaPatrocinadoPorNoUbicadoCommand $command): void
    {
        throw new \RuntimeException(
            'DarDeBajaPatrocinadoPorNoUbicadoHandler pendiente de implementación — '
            . 'requiere cerrar la Etapa 5 y la regla de negocio de reprogramación.'
        );
    }
}

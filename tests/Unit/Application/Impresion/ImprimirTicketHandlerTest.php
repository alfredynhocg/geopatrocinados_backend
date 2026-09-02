<?php

namespace Tests\Unit\Application\Impresion;

use App\Application\Impresion\Commands\ImprimirTicketCommand;
use App\Application\Impresion\DTOs\TicketDTO;
use App\Application\Impresion\Handlers\ImprimirTicketHandler;
use App\Domain\Impresion\Contracts\ImpresoraTermicaInterface;
use Tests\TestCase;

class ImprimirTicketHandlerTest extends TestCase
{
    public function test_delega_la_impresion_del_ticket_a_la_impresora(): void
    {
        $ticket = TicketDTO::fromArray([
            'titulo' => 'CENEFCO',
            'items' => [
                ['descripcion' => 'Inscripcion', 'cantidad' => 1, 'precio' => 150],
            ],
        ]);

        $impresora = \Mockery::mock(ImpresoraTermicaInterface::class);
        $impresora->shouldReceive('imprimirTicket')
            ->once()
            ->with($ticket);

        $handler = new ImprimirTicketHandler($impresora);

        $handler->handle(new ImprimirTicketCommand($ticket));
    }
}

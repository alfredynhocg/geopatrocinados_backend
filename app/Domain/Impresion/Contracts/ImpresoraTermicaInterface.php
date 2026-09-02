<?php

namespace App\Domain\Impresion\Contracts;

use App\Application\Impresion\DTOs\TicketDTO;

interface ImpresoraTermicaInterface
{
    public function imprimirTicket(TicketDTO $ticket): void;

    public function imprimirPruebaConexion(): void;
}

<?php

namespace App\Http\Controllers\Api;

use App\Application\Impresion\Commands\ImprimirTicketCommand;
use App\Application\Impresion\DTOs\TicketDTO;
use App\Application\Impresion\Handlers\ImprimirTicketHandler;
use App\Domain\Impresion\Contracts\ImpresoraTermicaInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Impresion\ImprimirTicketRequest;
use Illuminate\Http\JsonResponse;

class ImpresionController extends Controller
{
    public function __construct(
        private readonly ImprimirTicketHandler $imprimirTicketHandler,
        private readonly ImpresoraTermicaInterface $impresora,
    ) {}

    public function imprimir(ImprimirTicketRequest $request): JsonResponse
    {
        $this->imprimirTicketHandler->handle(new ImprimirTicketCommand(
            ticket: TicketDTO::fromArray($request->validated())
        ));

        return response()->json(['mensaje' => 'Ticket enviado a la impresora.']);
    }

    public function test(): JsonResponse
    {
        $this->impresora->imprimirPruebaConexion();

        return response()->json(['mensaje' => 'Impresión de prueba enviada.']);
    }
}

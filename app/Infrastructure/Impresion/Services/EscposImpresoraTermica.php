<?php

namespace App\Infrastructure\Impresion\Services;

use App\Application\Impresion\DTOs\ItemTicketDTO;
use App\Application\Impresion\DTOs\TicketDTO;
use App\Domain\Impresion\Contracts\ImpresoraTermicaInterface;
use App\Domain\Impresion\Exceptions\ImpresoraNoDisponibleException;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Mike42\Escpos\Printer;

class EscposImpresoraTermica implements ImpresoraTermicaInterface
{
    private readonly string $puerto;

    private int $anchoCaracteres;

    public function __construct(string $puerto = '')
    {
        $this->puerto = $puerto !== '' ? $puerto : (string) config('impresora.puerto');
        $this->anchoCaracteres = (int) config('impresora.ancho_caracteres', 32);
    }

    public function imprimirTicket(TicketDTO $ticket): void
    {
        try {
            $printer = $this->conectar();

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setEmphasis(true);
            $printer->text($ticket->titulo."\n");
            $printer->setEmphasis(false);

            foreach ($ticket->lineasEncabezado as $linea) {
                $printer->text($linea."\n");
            }

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text(str_repeat('-', $this->anchoCaracteres)."\n");

            foreach ($ticket->items as $item) {
                $printer->text($this->formatearLineaItem($item));
            }

            if (! empty($ticket->items)) {
                $printer->text(str_repeat('-', $this->anchoCaracteres)."\n");
                $printer->setJustification(Printer::JUSTIFY_RIGHT);
                $printer->text(sprintf("TOTAL: %.2f\n", $ticket->total()));
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            }

            foreach ($ticket->lineasPie as $linea) {
                $printer->text($linea."\n");
            }

            if ($ticket->qr) {
                $printer->feed(1);
                $printer->setJustification(Printer::JUSTIFY_CENTER);
                $printer->bitImage($this->generarImagenQr($ticket->qr));
                $printer->setJustification(Printer::JUSTIFY_LEFT);
            }

            $printer->feed(3);
            $printer->cut();
            $printer->close();
        } catch (\Throwable $e) {
            throw new ImpresoraNoDisponibleException($e->getMessage());
        }
    }

    public function imprimirPruebaConexion(): void
    {
        try {
            $printer = $this->conectar();

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("Prueba de conexion\n");
            $printer->text(now()->format('d/m/Y H:i:s')."\n");
            $printer->feed(2);
            $printer->cut();
            $printer->close();
        } catch (\Throwable $e) {
            throw new ImpresoraNoDisponibleException($e->getMessage());
        }
    }

    private function conectar(): Printer
    {
        // fopen()/WindowsPrintConnector abren el handle del puerto sin configurar baudrate/paridad;
        // en el puerto COM virtual Bluetooth SPP de esta impresora eso "funciona" sin error pero
        // los datos nunca llegan al dispositivo. SerialBluetoothPrintConnector usa
        // System.IO.Ports.SerialPort (.NET) vía PowerShell, que sí configura esos parámetros.
        $connector = new SerialBluetoothPrintConnector($this->puerto);

        return new Printer($connector);
    }

    private function generarImagenQr(string $contenido): Php8GdEscposImage
    {
        // El comando QR nativo del ESC/POS (GS ( k) falla en este clon de impresora
        // ("QR CREATE ERR!") con contenido de más de unos pocos caracteres — su firmware
        // no soporta versiones de QR grandes. Renderizamos el QR como imagen PNG (GD, sin
        // necesitar Imagick) y lo mandamos como gráfico rasterizado, que el firmware sí soporta.
        $options = new QROptions([
            'outputInterface' => QRGdImagePNG::class,
            'scale' => 4,
            'imageTransparent' => false,
            'outputBase64' => false,
        ]);

        $png = (new QRCode($options))->render($contenido);

        $tempFile = tempnam(sys_get_temp_dir(), 'qr').'.png';
        file_put_contents($tempFile, $png);

        try {
            $imagen = new Php8GdEscposImage($tempFile);
            // Forzar la carga/cacheo del raster ahora, mientras el archivo temporal
            // todavía existe (EscposImage lee el archivo de forma perezosa).
            $imagen->toRasterFormat();

            return $imagen;
        } finally {
            @unlink($tempFile);
        }
    }

    private function formatearLineaItem(ItemTicketDTO $item): string
    {
        $importe = sprintf('%.2f', $item->subtotal());
        $espacios = max(1, $this->anchoCaracteres - strlen($item->descripcion) - strlen($importe));

        return $item->descripcion.str_repeat(' ', $espacios).$importe."\n";
    }
}

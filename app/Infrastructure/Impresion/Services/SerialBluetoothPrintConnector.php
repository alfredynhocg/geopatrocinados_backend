<?php

namespace App\Infrastructure\Impresion\Services;

use Illuminate\Support\Facades\Process;
use Mike42\Escpos\PrintConnectors\PrintConnector;
use RuntimeException;

/**
 * Envía los datos ESC/POS por un puerto COM Bluetooth (SPP) usando
 * System.IO.Ports.SerialPort de .NET vía PowerShell.
 *
 * fopen()/fwrite() de PHP abren el handle del puerto sin configurar los
 * parámetros seriales (baudrate, paridad, bits de datos/parada); en los
 * puertos COM virtuales de Bluetooth SPP de esta impresora eso hace que la
 * escritura "tenga éxito" sin error pero los datos nunca lleguen al
 * dispositivo. SerialPort sí configura esos parámetros al abrir el puerto.
 */
class SerialBluetoothPrintConnector implements PrintConnector
{
    private string $buffer = '';

    public function __construct(
        private readonly string $puerto,
        private readonly int $baudRate = 9600,
    ) {}

    public function __destruct()
    {
        if ($this->buffer !== '') {
            trigger_error('Print connector was not finalized. Did you forget to close the printer?', E_USER_NOTICE);
        }
    }

    public function write($data)
    {
        $this->buffer .= $data;
    }

    public function read($len)
    {
        return false;
    }

    public function finalize()
    {
        $data = $this->buffer;
        $this->buffer = '';

        $tempFile = tempnam(sys_get_temp_dir(), 'escpos');
        file_put_contents($tempFile, $data);

        try {
            $script = base_path('app/Infrastructure/Impresion/Scripts/enviar-serial.ps1');

            // El proceso de PHP que atiende la request web (php artisan serve / servidor embebido)
            // puede no heredar TEMP/TMP/SystemRoot; sin ellos, el hosting administrado de
            // PowerShell falla al iniciar ("No se pudo cargar Windows PowerShell administrado").
            // Se pasan explícitos para que la inicialización del runtime de PowerShell sea confiable
            // sin importar el proceso padre desde el que se dispare la impresión.
            $env = [
                'TEMP' => getenv('TEMP') ?: sys_get_temp_dir(),
                'TMP' => getenv('TMP') ?: sys_get_temp_dir(),
                'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
                'windir' => getenv('windir') ?: 'C:\\Windows',
            ];

            $result = Process::timeout(15)->env($env)->run([
                'powershell', '-NoProfile', '-NonInteractive', '-ExecutionPolicy', 'Bypass', '-File', $script,
                '-Port', $this->puerto,
                '-BaudRate', (string) $this->baudRate,
                '-FilePath', $tempFile,
            ]);

            if (! $result->successful()) {
                throw new RuntimeException(trim($result->errorOutput()) ?: 'PowerShell falló al enviar datos al puerto serial.');
            }
        } finally {
            @unlink($tempFile);
        }
    }
}

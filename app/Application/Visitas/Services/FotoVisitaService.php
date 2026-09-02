<?php

namespace App\Application\Visitas\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Mecanismo de cifrado pendiente de decisión de negocio (docs/patrocinados/06-visitas.md
 * §"Decisiones de negocio", plan de revisión §5.7): at-rest del disco/bucket vs cifrado
 * aplicativo por archivo. Este Service asume que el disco 'patrocinados-privado' ya
 * viene cifrado at-rest (S3 SSE / disco cifrado) y NO cifra el binario en PHP —
 * si negocio exige cifrado aplicativo, es el único punto a modificar (almacenar()).
 */
class FotoVisitaService
{
    private const DISCO = 'patrocinados-privado';

    public function almacenar(UploadedFile $archivo): object
    {
        $hash = hash_file('sha256', $archivo->getRealPath());
        $clave = 'visitas/fotos/' . Str::uuid() . '.' . $archivo->getClientOriginalExtension();

        Storage::disk(self::DISCO)->putFileAs('', $archivo, $clave);

        return (object) [
            'clave'      => $clave,
            'hashSha256' => $hash,
            'cifrada'    => true, // at-rest del disco, ver nota de clase
        ];
    }

    public function urlFirmada(string $clave, int $minutos = 15): ?string
    {
        $disco = Storage::disk(self::DISCO);

        if (method_exists($disco, 'temporaryUrl')) {
            return $disco->temporaryUrl($clave, now()->addMinutes($minutos));
        }

        // TODO: endpoint de streaming autenticado si el driver de storage no soporta
        // temporaryUrl (p.ej. disco 'local' en desarrollo) — no exponer URL pública permanente.
        return null;
    }
}

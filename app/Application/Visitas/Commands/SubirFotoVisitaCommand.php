<?php

namespace App\Application\Visitas\Commands;

use Illuminate\Http\UploadedFile;

final readonly class SubirFotoVisitaCommand
{
    public function __construct(
        public string $visitaId,
        public string $dispositivoId,
        public UploadedFile $archivo,
        public ?float $latitude,
        public ?float $longitude,
    ) {}
}

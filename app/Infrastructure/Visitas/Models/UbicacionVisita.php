<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class UbicacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'ubicaciones_visitas';

    protected $fillable = ['visita_id', 'dispositivo_id', 'tecnico_id', 'fecha_captura', 'latitude', 'longitude', 'precision_metros', 'fuente'];

    protected $casts = ['fecha_captura' => 'datetime', 'latitude' => 'decimal:7', 'longitude' => 'decimal:7'];
}

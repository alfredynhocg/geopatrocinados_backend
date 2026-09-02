<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * `punto_geografico` (geography) no se declara como columna fillable/casteada:
 * la escribe únicamente EloquentUbicacionRepository vía SQL crudo, derivada
 * siempre de latitude/longitude. El modelo no la expone.
 */
class Ubicacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'ubicaciones';

    protected $fillable = [
        'comunidad_id', 'nombre', 'tipo', 'direccion',
        'latitude', 'longitude', 'precision_metros', 'estado', 'updated_by',
    ];

    protected $casts = [
        'latitude'         => 'decimal:7',
        'longitude'        => 'decimal:7',
        'precision_metros' => 'decimal:2',
        'estado'           => 'boolean',
    ];

    public function comunidad()
    {
        return $this->belongsTo(Comunidad::class, 'comunidad_id');
    }
}

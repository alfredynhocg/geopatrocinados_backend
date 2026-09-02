<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ElementoSincronizacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'elementos_sincronizacion';

    protected $fillable = [
        'lote_sincronizacion_id', 'tipo_entidad', 'entidad_id', 'operacion',
        'hash_datos', 'estado', 'intentos', 'mensaje_error', 'fecha_sincronizacion',
    ];

    protected $casts = [
        'fecha_sincronizacion' => 'datetime',
        'intentos'             => 'integer',
    ];

    public function lote()
    {
        return $this->belongsTo(LoteSincronizacion::class, 'lote_sincronizacion_id');
    }
}

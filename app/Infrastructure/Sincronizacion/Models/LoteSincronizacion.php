<?php

namespace App\Infrastructure\Sincronizacion\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Sin columna created_at en el docx (solo fecha_inicio + updated_at). */
class LoteSincronizacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    public const CREATED_AT = null;

    protected $table = 'lotes_sincronizacion';

    protected $fillable = [
        'dispositivo_id', 'user_id', 'fecha_inicio', 'fecha_fin',
        'registros_enviados', 'registros_recibidos', 'estado', 'mensaje_error',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin'    => 'datetime',
    ];

    public function elementos()
    {
        return $this->hasMany(ElementoSincronizacion::class, 'lote_sincronizacion_id');
    }
}

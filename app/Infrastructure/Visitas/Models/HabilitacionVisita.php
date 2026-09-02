<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class HabilitacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'habilitaciones_visitas';

    protected $fillable = [
        'visita_id', 'tecnico_id', 'dispositivo_id', 'authorized_by',
        'fecha_habilitacion', 'fecha_expiracion', 'estado', 'fecha_revocacion', 'revoked_by',
    ];

    protected $casts = [
        'fecha_habilitacion' => 'datetime',
        'fecha_expiracion'   => 'datetime',
        'fecha_revocacion'   => 'datetime',
    ];
}

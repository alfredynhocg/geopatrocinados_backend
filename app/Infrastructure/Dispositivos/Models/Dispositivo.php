<?php

namespace App\Infrastructure\Dispositivos\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/** Sin columna created_at en el docx (solo fecha_registro + updated_at). */
class Dispositivo extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    public const CREATED_AT = null;

    protected $table = 'dispositivos';

    protected $fillable = [
        'user_id', 'identificador_dispositivo', 'nombre_dispositivo', 'plataforma',
        'version_sistema', 'version_aplicacion', 'estado', 'ultima_sincronizacion_at',
        'fecha_registro', 'fecha_revocacion', 'revoked_by', 'updated_by',
    ];

    protected $casts = [
        'ultima_sincronizacion_at' => 'datetime',
        'fecha_registro'           => 'datetime',
        'fecha_revocacion'         => 'datetime',
    ];
}

<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AsignacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'asignaciones_visitas';

    protected $fillable = ['visita_id', 'tecnico_id', 'assigned_by', 'fecha_asignacion', 'fecha_desasignacion', 'estado'];

    protected $casts = [
        'fecha_asignacion'    => 'datetime',
        'fecha_desasignacion' => 'datetime',
        'estado'              => 'boolean',
    ];
}

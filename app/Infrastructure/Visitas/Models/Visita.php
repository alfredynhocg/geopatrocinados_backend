<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Visita extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'visitas';

    protected $fillable = [
        'plan_visita_id', 'patrocinado_id', 'user_id', 'motivo_visita_id',
        'fecha_programada', 'fecha_habilitacion', 'fecha_inicio', 'fecha_finalizacion',
        'estado', 'estado_revision', 'estado_sincronizacion', 'version',
        'intentos_reprogramacion', 'created_by',
    ];

    protected $casts = [
        'fecha_programada'    => 'date',
        'fecha_habilitacion'  => 'datetime',
        'fecha_inicio'        => 'datetime',
        'fecha_finalizacion'  => 'datetime',
        'version'             => 'integer',
        'intentos_reprogramacion' => 'integer',
    ];

    public function asignacionActiva()
    {
        return $this->hasOne(AsignacionVisita::class, 'visita_id')->where('estado', true);
    }

    public function habilitacionActiva()
    {
        return $this->hasOne(HabilitacionVisita::class, 'visita_id')->where('estado', 'ACTIVA');
    }

    public function observaciones()
    {
        return $this->hasMany(ObservacionVisita::class, 'visita_id');
    }

    public function fotos()
    {
        return $this->hasMany(FotoVisita::class, 'visita_id');
    }

    public function revisionVigente()
    {
        return $this->hasOne(RevisionVisita::class, 'visita_id')->latestOfMany('fecha_revision');
    }
}

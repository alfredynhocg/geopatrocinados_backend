<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patrocinado extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'patrocinados';

    protected $fillable = [
        'codigo', 'nombres', 'apellidos', 'fecha_nacimiento', 'sexo',
        'comunidad_id', 'ubicacion_id', 'unidad_educativa', 'nivel_educativo',
        'estado_id', 'updated_by',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function tutores()
    {
        return $this->hasMany(Tutor::class, 'patrocinado_id');
    }

    public function estadoPatrocinado()
    {
        return $this->belongsTo(EstadoPatrocinado::class, 'estado_id');
    }

    public function historialUbicaciones()
    {
        return $this->hasMany(HistorialUbicacion::class, 'patrocinado_id');
    }
}

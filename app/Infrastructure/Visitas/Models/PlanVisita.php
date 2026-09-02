<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PlanVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'planes_visitas';

    protected $fillable = ['plan', 'anio', 'fecha_inicio', 'fecha_fin', 'estado', 'created_by', 'updated_by'];

    protected $casts = ['fecha_inicio' => 'date', 'fecha_fin' => 'date'];
}

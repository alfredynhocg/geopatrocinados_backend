<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class MotivoVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'motivos_visitas';

    protected $fillable = ['motivo_visita', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}

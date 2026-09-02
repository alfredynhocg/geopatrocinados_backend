<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ObservacionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'observaciones_visitas';

    protected $fillable = ['visita_id', 'categoria_id', 'tipo', 'observacion', 'created_by'];
}

<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CategoriaObservacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'categorias_observaciones';

    protected $fillable = ['codigo', 'categoria_observaciones', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}

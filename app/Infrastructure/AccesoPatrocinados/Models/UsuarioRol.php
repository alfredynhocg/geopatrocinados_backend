<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot puro (PK compuesta usuario_id+rol_id, sin id propio). */
class UsuarioRol extends Pivot
{
    use UsaConexionPatrocinados;

    public $incrementing = false;

    protected $table = 'usuarios_roles';

    protected $fillable = ['usuario_id', 'rol_id', 'updated_by'];
}

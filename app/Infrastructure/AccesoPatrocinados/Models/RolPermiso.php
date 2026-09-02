<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** Pivot puro (PK compuesta rol_id+permiso_id, sin id propio). */
class RolPermiso extends Pivot
{
    use UsaConexionPatrocinados;

    public $incrementing = false;

    protected $table = 'roles_permisos';

    protected $fillable = ['rol_id', 'permiso_id', 'updated_by'];
}

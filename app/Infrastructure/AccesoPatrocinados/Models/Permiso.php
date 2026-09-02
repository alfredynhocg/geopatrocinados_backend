<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'permisos';

    protected $fillable = ['nombre', 'modulo', 'accion', 'descripcion', 'updated_by'];

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'roles_permisos', 'permiso_id', 'rol_id')
            ->using(RolPermiso::class)
            ->withTimestamps();
    }
}

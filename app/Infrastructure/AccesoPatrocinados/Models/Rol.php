<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'roles';

    protected $fillable = ['nombre', 'descripcion', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];

    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'roles_permisos', 'rol_id', 'permiso_id')
            ->using(RolPermiso::class)
            ->withTimestamps();
    }

    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'usuarios_roles', 'rol_id', 'usuario_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }
}

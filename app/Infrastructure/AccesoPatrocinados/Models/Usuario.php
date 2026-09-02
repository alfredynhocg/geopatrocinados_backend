<?php

namespace App\Infrastructure\AccesoPatrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Modelo de autenticación propio del módulo Patrocinados, independiente de
 * App\Models\User (mentabit). Sanctum multi-modelo: el guard `sanctum`
 * resuelve el modelo dueño del token (tokenable_type), no un provider fijo.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasUuids, Notifiable, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'usuarios';

    protected $fillable = [
        'username', 'email', 'password_hash', 'nombres', 'apellidos', 'telefono',
        'estado', 'intentos_fallidos', 'bloqueado_hasta', 'ultimo_login_at',
        'password_cambiado_at', 'updated_by',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'intentos_fallidos'    => 'integer',
        'bloqueado_hasta'      => 'datetime',
        'ultimo_login_at'      => 'datetime',
        'password_cambiado_at' => 'datetime',
    ];

    /** Authenticatable espera este accessor para Hash::check() vía los guards estándar. */
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'usuarios_roles', 'usuario_id', 'rol_id')
            ->using(UsuarioRol::class)
            ->withTimestamps();
    }

    /** Resuelve el permiso vía roles, sin pasar por Gate/Policy de Laravel. */
    public function tienePermiso(string $nombrePermiso): bool
    {
        return $this->roles()
            ->whereHas('permisos', fn ($q) => $q->where('nombre', $nombrePermiso))
            ->exists();
    }
}

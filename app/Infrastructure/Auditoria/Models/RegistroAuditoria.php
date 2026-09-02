<?php

namespace App\Infrastructure\Auditoria\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Model;

/**
 * Única tabla del módulo con PK bigint autoincremental (no UUID) — insert-only
 * de alto volumen, sin updated_at.
 */
class RegistroAuditoria extends Model
{
    use UsaConexionPatrocinados;

    public const UPDATED_AT = null;

    protected $table = 'registros_auditoria';

    protected $fillable = [
        'user_id', 'dispositivo_id', 'accion', 'modulo', 'tipo_entidad', 'entidad_id',
        'valores_anteriores', 'valores_nuevos', 'direccion_ip', 'user_agent',
    ];

    protected $casts = [
        'valores_anteriores' => 'array',
        'valores_nuevos'     => 'array',
    ];
}

<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Sin SoftDeletes: el docx no marca deleted_at en esta tabla y la
 * hoja de ruta prohíbe agregarlo por analogía con `patrocinados`.
 */
class HistorialUbicacion extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'historial_ubicaciones';

    protected $fillable = [
        'patrocinado_id', 'comunidad_id', 'ubicacion_id',
        'fecha_inicio', 'fecha_fin', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    public function patrocinado()
    {
        return $this->belongsTo(Patrocinado::class, 'patrocinado_id');
    }
}

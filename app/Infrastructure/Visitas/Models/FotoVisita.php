<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FotoVisita extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'fotos_visitas';

    protected $fillable = [
        'visita_id', 'clave_almacenamiento', 'nombre_archivo', 'tipo_archivo', 'tamanio',
        'ancho', 'alto', 'hash_sha256', 'fecha_captura', 'latitude', 'longitude', 'cifrada',
    ];

    protected $hidden = ['clave_almacenamiento'];

    protected $casts = ['fecha_captura' => 'datetime', 'cifrada' => 'boolean'];
}

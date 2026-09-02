<?php

namespace App\Infrastructure\Visitas\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RevisionVisita extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'revisiones_visitas';

    protected $fillable = ['visita_id', 'user_id', 'fecha_revision', 'estado', 'comentarios', 'requiere_correccion'];

    protected $casts = ['fecha_revision' => 'datetime', 'requiere_correccion' => 'boolean'];
}

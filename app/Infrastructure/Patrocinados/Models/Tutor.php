<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tutor extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'tutores';

    protected $fillable = [
        'patrocinado_id', 'nombres', 'apellidos', 'tipo_parentesco_id',
        'telefono', 'direccion', 'estado', 'updated_by',
    ];

    protected $casts = ['estado' => 'boolean'];

    public function patrocinado()
    {
        return $this->belongsTo(Patrocinado::class, 'patrocinado_id');
    }

    public function tipoParentesco()
    {
        return $this->belongsTo(TipoParentesco::class, 'tipo_parentesco_id');
    }
}

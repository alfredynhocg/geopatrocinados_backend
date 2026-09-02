<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'departamento';

    protected $fillable = ['codigo', 'departamento', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'departamento_id');
    }
}

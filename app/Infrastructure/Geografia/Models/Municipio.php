<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'municipios';

    protected $fillable = ['departamento_id', 'codigo', 'municipio', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function comunidades()
    {
        return $this->hasMany(Comunidad::class, 'municipio_id');
    }
}

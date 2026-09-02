<?php

namespace App\Infrastructure\Geografia\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Comunidad extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'comunidades';

    protected $fillable = ['municipio_id', 'codigo', 'comunidad', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function ubicaciones()
    {
        return $this->hasMany(Ubicacion::class, 'comunidad_id');
    }
}

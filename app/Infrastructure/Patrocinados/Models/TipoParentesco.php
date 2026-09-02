<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class TipoParentesco extends Model
{
    use HasUuids, UsaConexionPatrocinados;

    protected $table = 'tipos_parentescos';

    protected $fillable = ['parentesco', 'estado', 'updated_by'];

    protected $casts = ['estado' => 'boolean'];
}

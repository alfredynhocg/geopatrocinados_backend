<?php

namespace App\Infrastructure\Patrocinados\Models;

use App\Infrastructure\Patrocinados\Concerns\UsaConexionPatrocinados;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EstadoPatrocinado extends Model
{
    use HasUuids, SoftDeletes, UsaConexionPatrocinados;

    protected $table = 'estados_patrocinados';

    protected $fillable = ['estado', 'updated_by'];

    public function patrocinados()
    {
        return $this->hasMany(Patrocinado::class, 'estado_id');
    }
}

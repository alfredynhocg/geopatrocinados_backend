<?php

namespace App\Infrastructure\Servicios\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Servicio extends Model
{
    use SoftDeletes;

    protected $table = 'web_servicio';

    protected $fillable = [
        'titulo',
        'slug',
        'categoria',
        'descripcion_corta',
        'descripcion',
        'icono',
        'imagen_url',
        'imagen_alt',
        'whatsapp',
        'precio_desde',
        'moneda',
        'modalidad',
        'destacado',
        'orden',
        'estado',
        'meta_titulo',
        'meta_descripcion',
    ];

    protected $casts = [
        'precio_desde' => 'decimal:2',
        'destacado'    => 'boolean',
        'orden'        => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $base = Str::slug($model->titulo ?? '') ?: 'servicio';
                $slug = $base;
                $i    = 1;
                while (static::withTrashed()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $model->slug = $slug;
            }
        });
    }
}

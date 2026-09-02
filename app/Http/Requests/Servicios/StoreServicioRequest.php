<?php

namespace App\Http\Requests\Servicios;

use Illuminate\Foundation\Http\FormRequest;

class StoreServicioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'             => ['required', 'string', 'max:200'],
            'slug'               => ['nullable', 'string', 'max:300'],
            'categoria'          => ['nullable', 'string', 'in:mentoria,infraestructura,desarrollo,consultoria,capacitacion,otro'],
            'descripcion_corta'  => ['nullable', 'string', 'max:500'],
            'descripcion'        => ['nullable', 'string'],
            'icono'              => ['nullable', 'string', 'max:100'],
            'imagen_url'         => ['nullable', 'string', 'max:255'],
            'imagen_alt'         => ['nullable', 'string', 'max:255'],
            'whatsapp'           => ['nullable', 'string', 'max:20', 'regex:/^[0-9]+$/'],
            'precio_desde'       => ['nullable', 'numeric', 'min:0'],
            'moneda'             => ['nullable', 'string', 'max:10'],
            'modalidad'          => ['nullable', 'string', 'in:presencial,virtual,hibrido'],
            'destacado'          => ['nullable', 'boolean'],
            'orden'              => ['nullable', 'integer'],
            'estado'             => ['nullable', 'string', 'in:publicado,borrador,archivado'],
            'meta_titulo'        => ['nullable', 'string', 'max:300'],
            'meta_descripcion'   => ['nullable', 'string', 'max:500'],
        ];
    }
}

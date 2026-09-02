<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUbicacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comunidad_id'      => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'nombre'            => ['required', 'string', 'max:180'],
            'tipo'              => ['nullable', Rule::in(['DOMICILIO', 'ESCUELA', 'PUNTO_REFERENCIA', 'OTRO'])],
            'direccion'         => ['nullable', 'string'],
            'latitude'          => ['required', 'numeric', 'between:-90,90'],
            'longitude'         => ['required', 'numeric', 'between:-180,180'],
            'precision_metros'  => ['nullable', 'numeric', 'min:0'],
            'estado'            => ['boolean'],
        ];
    }
}

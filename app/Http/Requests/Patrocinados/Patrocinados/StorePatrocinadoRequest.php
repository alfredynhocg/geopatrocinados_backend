<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StorePatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'            => ['required', 'string', 'max:60', 'unique:pgsql_patrocinados.patrocinados,codigo'],
            'nombres'           => ['required', 'string', 'max:120'],
            'apellidos'         => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento'  => ['nullable', 'date'],
            'sexo'              => ['nullable', 'string', 'max:30'],
            'comunidad_id'      => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'ubicacion_id'      => ['nullable', 'uuid', 'exists:pgsql_patrocinados.ubicaciones,id'],
            'unidad_educativa'  => ['nullable', 'string', 'max:200'],
            'nivel_educativo'   => ['nullable', 'string', 'max:120'],
            'estado_id'         => ['required', 'uuid', 'exists:pgsql_patrocinados.estados_patrocinados,id'],
        ];
    }
}

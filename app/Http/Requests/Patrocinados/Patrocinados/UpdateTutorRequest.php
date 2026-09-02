<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTutorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'             => ['required', 'string', 'max:120'],
            'apellidos'           => ['required', 'string', 'max:160'],
            'tipo_parentesco_id'  => ['required', 'uuid', 'exists:pgsql_patrocinados.tipos_parentescos,id'],
            'telefono'            => ['nullable', 'string', 'max:40'],
            'direccion'           => ['required', 'string', 'max:160'],
            'estado'              => ['boolean'],
        ];
    }
}

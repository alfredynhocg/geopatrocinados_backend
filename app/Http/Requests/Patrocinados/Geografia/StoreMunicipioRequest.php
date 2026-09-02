<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.departamento,id'],
            'codigo'          => ['nullable', 'string', 'max:30', 'unique:pgsql_patrocinados.municipios,codigo'],
            'municipio'       => ['required', 'string', 'max:150'],
            'estado'          => ['boolean'],
        ];
    }
}

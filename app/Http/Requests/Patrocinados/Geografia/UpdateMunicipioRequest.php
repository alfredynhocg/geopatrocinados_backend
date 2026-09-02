<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMunicipioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'departamento_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.departamento,id'],
            'codigo'          => ['nullable', 'string', 'max:30', Rule::unique('pgsql_patrocinados.municipios', 'codigo')->ignore($this->route('id'))],
            'municipio'       => ['required', 'string', 'max:150'],
            'estado'          => ['boolean'],
        ];
    }
}

<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'       => ['nullable', 'string', 'max:30', Rule::unique('pgsql_patrocinados.departamento', 'codigo')->ignore($this->route('id'))],
            'departamento' => ['required', 'string', 'max:150'],
            'estado'       => ['boolean'],
        ];
    }
}

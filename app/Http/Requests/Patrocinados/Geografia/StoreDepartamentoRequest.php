<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'       => ['nullable', 'string', 'max:30', 'unique:pgsql_patrocinados.departamento,codigo'],
            'departamento' => ['required', 'string', 'max:150'],
            'estado'       => ['boolean'],
        ];
    }
}

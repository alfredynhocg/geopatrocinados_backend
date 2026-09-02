<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:80', Rule::unique('pgsql_patrocinados.roles', 'nombre')->ignore($this->route('id'))],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado'      => ['boolean'],
        ];
    }
}

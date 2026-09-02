<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:120', Rule::unique('pgsql_patrocinados.permisos', 'nombre')->ignore($this->route('id'))],
            'modulo'      => ['required', 'string', 'max:80'],
            'accion'      => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}

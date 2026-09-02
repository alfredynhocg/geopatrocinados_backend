<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StorePermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:120', 'unique:pgsql_patrocinados.permisos,nombre'],
            'modulo'      => ['required', 'string', 'max:80'],
            'accion'      => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}

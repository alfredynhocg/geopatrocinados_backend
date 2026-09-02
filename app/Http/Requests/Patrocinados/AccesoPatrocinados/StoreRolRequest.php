<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'      => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.roles,nombre'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'estado'      => ['boolean'],
        ];
    }
}

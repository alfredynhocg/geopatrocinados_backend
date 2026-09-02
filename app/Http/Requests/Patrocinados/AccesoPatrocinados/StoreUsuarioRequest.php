<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username'  => ['required', 'string', 'max:80', 'unique:pgsql_patrocinados.usuarios,username'],
            'email'     => ['required', 'email', 'max:180', 'unique:pgsql_patrocinados.usuarios,email'],
            'password'  => ['required', 'string', 'min:8'],
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:40'],
            'estado'    => ['nullable', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO'])],
        ];
    }
}

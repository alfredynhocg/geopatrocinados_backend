<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Sin username/email/password: cambios de credenciales van por un flujo aparte, fuera del alcance de esta etapa. */
class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'   => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:120'],
            'telefono'  => ['nullable', 'string', 'max:40'],
            'estado'    => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'BLOQUEADO'])],
        ];
    }
}

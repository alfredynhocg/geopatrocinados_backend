<?php

namespace App\Http\Requests\Patrocinados\Sincronizacion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcesarElementoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_entidad' => ['required', 'string', 'max:100'],
            'entidad_id'   => ['required', 'uuid'],
            'operacion'    => ['required', Rule::in(['CREATE', 'UPDATE', 'DELETE'])],
            'hash_datos'   => ['nullable', 'string', 'size:64'],
            'payload'      => ['nullable', 'array'],
        ];
    }
}

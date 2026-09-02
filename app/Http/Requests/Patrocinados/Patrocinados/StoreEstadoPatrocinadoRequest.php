<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstadoPatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(['ACTIVO', 'NO_ENCONTRADO', 'INACTIVO_NO_UBICADO', 'MAYOR_DE_EDAD'])],
        ];
    }
}

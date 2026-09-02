<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRevisionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'estado'      => ['required', Rule::in(['APROBADA', 'RECHAZADA', 'REQUIERE_CORRECCION'])],
            'comentarios' => ['nullable', 'string'],
        ];
    }
}

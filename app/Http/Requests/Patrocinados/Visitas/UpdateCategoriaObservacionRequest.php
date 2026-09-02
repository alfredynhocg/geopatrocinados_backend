<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoriaObservacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codigo'                  => ['required', 'string', 'max:20'],
            'categoria_observaciones' => ['required', 'string', 'max:120'],
            'descripcion'             => ['nullable', 'string', 'max:255'],
            'estado'                  => ['sometimes', 'boolean'],
        ];
    }
}

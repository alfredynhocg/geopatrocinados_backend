<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMotivoVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_visita' => ['required', 'string', 'max:120'],
            'descripcion'   => ['nullable', 'string', 'max:255'],
            'estado'        => ['sometimes', 'boolean'],
        ];
    }
}

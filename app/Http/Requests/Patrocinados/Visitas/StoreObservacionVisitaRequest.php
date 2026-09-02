<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObservacionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'categoria_id'   => ['nullable', 'uuid', 'exists:pgsql_patrocinados.categorias_observaciones,id'],
            'tipo'           => ['required', Rule::in(['GENERAL', 'EDUCATIVA', 'SALUD', 'FAMILIAR'])],
            'observacion'    => ['required', 'string'],
        ];
    }
}

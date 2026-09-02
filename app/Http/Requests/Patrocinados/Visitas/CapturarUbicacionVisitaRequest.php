<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapturarUbicacionVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id'   => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'tecnico_id'       => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
            'latitude'         => ['required', 'numeric', 'between:-90,90'],
            'longitude'        => ['required', 'numeric', 'between:-180,180'],
            'precision_metros' => ['nullable', 'numeric', 'min:0'],
            'fuente'           => ['required', Rule::in(['GPS', 'RED', 'MANUAL'])],
        ];
    }
}

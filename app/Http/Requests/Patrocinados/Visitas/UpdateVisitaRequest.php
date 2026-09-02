<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan_visita_id'   => ['nullable', 'uuid', 'exists:pgsql_patrocinados.planes_visitas,id'],
            'motivo_visita_id' => ['nullable', 'uuid', 'exists:pgsql_patrocinados.motivos_visitas,id'],
            'fecha_programada' => ['nullable', 'date'],
        ];
    }
}

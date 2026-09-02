<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class ReasignarVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nuevo_tecnico_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
        ];
    }
}

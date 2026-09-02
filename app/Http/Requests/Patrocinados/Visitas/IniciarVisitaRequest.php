<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class IniciarVisitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dispositivo_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
        ];
    }
}

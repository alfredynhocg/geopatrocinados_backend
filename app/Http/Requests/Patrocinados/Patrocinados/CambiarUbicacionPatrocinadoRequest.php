<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class CambiarUbicacionPatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comunidad_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.comunidades,id'],
            'ubicacion_id' => ['nullable', 'uuid', 'exists:pgsql_patrocinados.ubicaciones,id'],
        ];
    }
}

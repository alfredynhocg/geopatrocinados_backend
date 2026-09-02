<?php

namespace App\Http\Requests\Patrocinados\Visitas;

use Illuminate\Foundation\Http\FormRequest;

class HabilitarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tecnico_id'       => ['required', 'uuid', 'exists:pgsql_patrocinados.usuarios,id'],
            'dispositivo_id'   => ['required', 'uuid', 'exists:pgsql_patrocinados.dispositivos,id'],
            'fecha_expiracion' => ['required', 'date', 'after:now'],
        ];
    }
}

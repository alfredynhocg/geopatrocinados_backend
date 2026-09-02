<?php

namespace App\Http\Requests\Patrocinados\Sincronizacion;

use Illuminate\Foundation\Http\FormRequest;

class IniciarLoteRequest extends FormRequest
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

<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre_dispositivo' => ['nullable', 'string', 'max:150'],
            'version_sistema'    => ['nullable', 'string', 'max:50'],
            'version_aplicacion' => ['nullable', 'string', 'max:50'],
        ];
    }
}

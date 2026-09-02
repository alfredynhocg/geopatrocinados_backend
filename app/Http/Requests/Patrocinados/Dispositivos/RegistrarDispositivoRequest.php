<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identificador_dispositivo' => ['required', 'string', 'max:180'],
            'nombre_dispositivo'        => ['nullable', 'string', 'max:150'],
            'plataforma'                => ['required', 'string', 'max:30'],
            'version_sistema'           => ['nullable', 'string', 'max:50'],
            'version_aplicacion'        => ['nullable', 'string', 'max:50'],
        ];
    }
}

<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

class RevocarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo' => ['nullable', 'string', 'max:255'],
        ];
    }
}

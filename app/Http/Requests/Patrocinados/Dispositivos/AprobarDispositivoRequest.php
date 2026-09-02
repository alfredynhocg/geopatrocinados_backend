<?php

namespace App\Http\Requests\Patrocinados\Dispositivos;

use Illuminate\Foundation\Http\FormRequest;

/** Sin body requerido — la aprobación no lleva datos adicionales por ahora. */
class AprobarDispositivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}

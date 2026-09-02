<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoParentescoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parentesco' => ['required', 'string', 'max:120'],
            'estado'     => ['boolean'],
        ];
    }
}

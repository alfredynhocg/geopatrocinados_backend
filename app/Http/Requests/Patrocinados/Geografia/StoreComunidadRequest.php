<?php

namespace App\Http\Requests\Patrocinados\Geografia;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'municipio_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.municipios,id'],
            'codigo'       => ['nullable', 'string', 'max:30'],
            'comunidad'    => ['required', 'string', 'max:180'],
            'estado'       => ['boolean'],
        ];
    }
}

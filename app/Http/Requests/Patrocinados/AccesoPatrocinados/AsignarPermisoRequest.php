<?php

namespace App\Http\Requests\Patrocinados\AccesoPatrocinados;

use Illuminate\Foundation\Http\FormRequest;

class AsignarPermisoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'permiso_id' => ['required', 'uuid', 'exists:pgsql_patrocinados.permisos,id'],
        ];
    }
}

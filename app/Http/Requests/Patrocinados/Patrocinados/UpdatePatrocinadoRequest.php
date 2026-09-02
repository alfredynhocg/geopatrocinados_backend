<?php

namespace App\Http\Requests\Patrocinados\Patrocinados;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Deliberadamente sin comunidad_id/ubicacion_id/codigo: cambiar la
 * ubicación de un patrocinado va por CambiarUbicacionPatrocinadoRequest.
 */
class UpdatePatrocinadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombres'           => ['required', 'string', 'max:120'],
            'apellidos'         => ['nullable', 'string', 'max:160'],
            'fecha_nacimiento'  => ['nullable', 'date'],
            'sexo'              => ['nullable', 'string', 'max:30'],
            'unidad_educativa'  => ['nullable', 'string', 'max:200'],
            'nivel_educativo'   => ['nullable', 'string', 'max:120'],
            'estado_id'         => ['required', 'uuid', 'exists:pgsql_patrocinados.estados_patrocinados,id'],
        ];
    }
}

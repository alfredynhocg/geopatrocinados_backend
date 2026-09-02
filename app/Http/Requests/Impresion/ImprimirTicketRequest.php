<?php

namespace App\Http\Requests\Impresion;

use Illuminate\Foundation\Http\FormRequest;

class ImprimirTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:100'],
            'lineas_encabezado' => ['nullable', 'array'],
            'lineas_encabezado.*' => ['nullable', 'string', 'max:100'],
            'items' => ['nullable', 'array'],
            'items.*.descripcion' => ['required_with:items', 'string', 'max:100'],
            'items.*.cantidad' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.precio' => ['required_with:items', 'numeric', 'min:0'],
            'lineas_pie' => ['nullable', 'array'],
            'lineas_pie.*' => ['nullable', 'string', 'max:100'],
            'qr' => ['nullable', 'string', 'max:500'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProgramaRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'co' => [
                'nullable',
                'string',
                'max:10',
                'unique:programa,cod', // Código único
            ],
            'no' => [
                'required',
                'string',
                'max:200',
                'min:3',
            ],
            'ni' => [
                'required',
                'integer',
                'min:1',
                'exists:nivel,id', // Nivel debe existir
            ],
            'pr' => [
                'required',
                'integer',
                'min:1',
                'exists:institucion,id', // Institución debe existir
            ],
            'ac' => [
                'required',
                'integer',
                'in:0,1',
            ],
        ];
    }

    /**
     * Obtiene los mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'co.unique' => 'El código del programa ya existe.',
            'co.max' => 'El código no puede exceder 10 caracteres.',
            'no.required' => 'El nombre del programa es requerido.',
            'no.min' => 'El nombre del programa debe tener al menos 3 caracteres.',
            'no.max' => 'El nombre del programa no puede exceder 200 caracteres.',
            'ni.required' => 'El nivel es requerido.',
            'ni.exists' => 'El nivel seleccionado no existe.',
            'pr.required' => 'La institución es requerida.',
            'pr.exists' => 'La institución seleccionada no existe.',
            'ac.required' => 'El estado es requerido.',
            'ac.in' => 'El estado debe ser 0 (inactivo) o 1 (activo).',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Convertir valores esperados
        $this->merge([
            'ni' => (int) ($this->input('ni') ?? 0),
            'pr' => (int) ($this->input('pr') ?? 0),
            'ac' => (int) ($this->input('ac') ?? 1),
        ]);
    }
}

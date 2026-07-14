<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquivalenciaRequest extends FormRequest
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
            'ap' => [
                'required',
                'string',
                'max:20',
                'exists:asignatura,cod', // Asignatura PCA debe existir
            ],
            'ae' => [
                'required',
                'string',
                'max:20',
                'exists:asignatura,cod', // Asignatura Externa debe existir
                'different:ap', // No puede ser igual a la otra
            ],
        ];
    }

    /**
     * Obtiene los mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'ap.required' => 'La asignatura PCA es requerida.',
            'ap.exists' => 'La asignatura PCA seleccionada no existe.',
            'ae.required' => 'La asignatura externa es requerida.',
            'ae.exists' => 'La asignatura externa seleccionada no existe.',
            'ae.different' => 'No se puede crear equivalencia entre la misma asignatura.',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        // Limpiar valores
        $this->merge([
            'ap' => trim($this->input('ap') ?? ''),
            'ae' => trim($this->input('ae') ?? ''),
        ]);
    }
}

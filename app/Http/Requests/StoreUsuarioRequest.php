<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUsuarioRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return true; // El middleware saiq.auth ya valida autenticación
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        return [
            'no' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[a-zA-Z0-9_]+$/',
                'unique:usuario,id', // Validar que no existe
            ],
            'co' => [
                'required',
                'string',
                'min:6',
                'max:100',
            ],
            'pr' => [
                'required',
                'string',
                'max:10',
                'not_in:0',
                'exists:programa,cod', // Validar que programa existe
            ],
            'fi' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png',
                'max:5120', // 5MB
            ],
        ];
    }

    /**
     * Obtiene los mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'no.required' => 'El nombre de usuario es requerido.',
            'no.unique' => 'El usuario ya existe en el sistema.',
            'no.regex' => 'El usuario solo puede contener letras, números y guiones bajos.',
            'no.min' => 'El usuario debe tener al menos 3 caracteres.',
            'no.max' => 'El usuario no puede exceder 20 caracteres.',
            'co.required' => 'La contraseña es requerida.',
            'co.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'pr.required' => 'El programa es requerido.',
            'pr.exists' => 'El programa seleccionado no existe.',
            'fi.mimes' => 'La firma debe ser un archivo de imagen (JPG, PNG).',
            'fi.max' => 'La firma no puede exceder 5MB.',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'pr' => $this->input('pr') ?? '0',
        ]);
    }
}

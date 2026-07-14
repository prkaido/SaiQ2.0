<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInstitucionRequest extends FormRequest
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
            'no' => [
                'required',
                'string',
                'max:200',
                'min:3',
                'unique:institucion,nombre', // Nombre único
            ],
            'ab' => [
                'nullable',
                'string',
                'max:10',
                'unique:institucion,abrev', // Abreviatura única
            ],
            'ti' => [
                'nullable',
                'string',
                'max:20',
                'in:IES,EMPRESA,EXTERNA,SENA', // Solo valores válidos
            ],
            'ci' => [
                'nullable',
                'string',
                'max:80',
            ],
            'pa' => [
                'nullable',
                'string',
                'max:80',
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
            'no.required' => 'El nombre de la institución es requerido.',
            'no.unique' => 'La institución con este nombre ya existe.',
            'no.min' => 'El nombre debe tener al menos 3 caracteres.',
            'no.max' => 'El nombre no puede exceder 200 caracteres.',
            'ab.unique' => 'La abreviatura ya está asignada a otra institución.',
            'ab.max' => 'La abreviatura no puede exceder 10 caracteres.',
            'ti.in' => 'El tipo debe ser: IES, EMPRESA, EXTERNA o SENA.',
            'ac.required' => 'El estado es requerido.',
            'ac.in' => 'El estado debe ser 0 (inactivo) o 1 (activo).',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'ac' => (int) ($this->input('ac') ?? 1),
        ]);
    }
}

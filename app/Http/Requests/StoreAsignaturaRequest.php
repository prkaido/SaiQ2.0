<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreAsignaturaRequest extends FormRequest
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
                'max:20',
                Rule::unique('asignatura', 'cod')->where(fn ($query) => $query->where('programa', $this->input('pr'))),
            ],
            'no' => [
                'required',
                'string',
                'max:250',
                'min:3',
            ],
            'pr' => [
                'required',
                'string',
                'max:10',
                'not_in:0',
                'exists:programa,cod', // Programa debe existir
            ],
            'pl' => [
                'nullable',
                'integer',
                'min:1',
                'exists:plan,id',
            ],
            'ni' => [
                'nullable',
                'integer',
                'min:1',
                'max:20',
            ],
            'cr' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'is' => [
                'nullable',
                'integer',
                'min:0',
                'max:50',
            ],
        ];
    }

    /**
     * Obtiene los mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'co.max' => 'El código no puede exceder 20 caracteres.',
            'co.unique' => 'El código de asignatura ya existe para este programa.',
            'no.required' => 'El nombre de la asignatura es requerido.',
            'no.min' => 'El nombre debe tener al menos 3 caracteres.',
            'no.max' => 'El nombre no puede exceder 250 caracteres.',
            'pr.required' => 'El programa es requerido.',
            'pr.exists' => 'El programa seleccionado no existe.',
            'pl.exists' => 'El plan seleccionado no existe.',
            'cr.numeric' => 'Los créditos deben ser un número válido.',
            'cr.min' => 'Los créditos no pueden ser negativos.',
            'is.integer' => 'Las horas de semana deben ser un número entero.',
            'is.min' => 'Las horas de semana no pueden ser negativas.',
        ];
    }

    /**
     * Prepara los datos para validación.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'pl' => $this->input('pl') ? (int) $this->input('pl') : null,
            'ni' => $this->input('ni') ? (int) $this->input('ni') : null,
            'cr' => $this->input('cr') ? (float) $this->input('cr') : null,
            'is' => $this->input('is') ? (int) $this->input('is') : null,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->filled('pl') && $this->filled('pr')) {
                $plan = DB::table('plan')->where('id', $this->input('pl'))->first();

                if (!$plan || $plan->programa !== $this->input('pr')) {
                    $validator->errors()->add('pl', 'El plan no pertenece al programa seleccionado.');
                }
            }
        });
    }
}

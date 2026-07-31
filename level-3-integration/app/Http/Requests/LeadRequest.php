<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class LeadRequest extends FormRequest
{
    // коды украинских мобильных операторов
    private const OPERATORS = [
        '39', '50', '63', '66', '67', '68',
        '73', '91', '92', '93', '94', '95',
        '96', '97', '98', '99',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => preg_replace('/[^\d+]/', '', (string) $this->input('phone')),
        ]);
    }

    public function rules(): array
    {
        $codes = implode('|', self::OPERATORS);

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:60', 'regex:/^[\p{L}\x{2019}\'\- ]+$/u'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:60', 'regex:/^[\p{L}\x{2019}\'\- ]+$/u'],
            'email' => ['nullable', 'email:rfc', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+380(' . $codes . ')\d{7}$/'],
            'message' => ['nullable', 'string', 'max:2000'],

            // ловушка для ботов: поле скрыто в разметке, человек его не заполнит
            'company' => ['prohibited'],
            'form_started_at' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.regex' => 'Ім\'я може містити тільки літери, апостроф і дефіс.',
            'last_name.regex' => 'Прізвище може містити тільки літери, апостроф і дефіс.',
            'phone.regex' => 'Вкажіть номер у форматі +380XXXXXXXXX з кодом українського оператора.',
            'company.prohibited' => 'Заявку відхилено.',
            'form_started_at.required' => 'Заявку відхилено.',
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $started = (int) $this->input('form_started_at');
            if ($started <= 0) {
                return;
            }

            // форму, заполненную быстрее трёх секунд, отправил не человек
            if (now()->timestamp - intdiv($started, 1000) < 3) {
                $validator->errors()->add('form_started_at', 'Заявку відхилено.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator, response()->json([
            'ok' => false,
            'errors' => $validator->errors(),
        ], 422));
    }
}

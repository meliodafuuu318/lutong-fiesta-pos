<?php

namespace App\Http\Requests\Expense;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Positive: money going out. Negative: money coming in (income from outside the
            // transactions, or change returned from money that was taken out earlier).
            'amount' => [
                'required',
                'numeric',
                'between:-99999999.99,99999999.99',
                fn (string $attribute, mixed $value, \Closure $fail) => (float) $value === 0.0
                    ? $fail('The amount can\'t be zero.')
                    : null,
            ],
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}

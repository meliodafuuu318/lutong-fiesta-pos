<?php

namespace App\Http\Requests\DailyItem;

use Illuminate\Foundation\Http\FormRequest;

class WebUpdateDailyItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'status' => ['required', 'in:AVAILABLE,SOLD OUT'],
        ];
    }

    public function messages(): array
    {
        return [
            'price.required' => 'Enter a price.',
            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price can\'t be negative.',
            'price.max' => 'That price is too high.',
            'status.required' => 'Choose a status.',
            'status.in' => 'Choose a valid status.',
        ];
    }
}

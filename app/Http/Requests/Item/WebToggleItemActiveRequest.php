<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class WebToggleItemActiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}

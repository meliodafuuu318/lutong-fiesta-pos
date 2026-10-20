<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;

class WebStoreItemRequest extends FormRequest
{
    /** Keeps these errors apart from any other form on the page. */
    protected $errorBag = 'itemForm';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'default_price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Enter an item name.',
            'name.max' => 'The name is too long.',
            'default_price.required' => 'Enter a default price.',
            'default_price.numeric' => 'The price must be a number.',
            'default_price.min' => 'The price can\'t be negative.',
            'default_price.max' => 'That price is too high.',
            'category_id.required' => 'Choose a category.',
            'category_id.exists' => 'Choose a valid category.',
        ];
    }
}

<?php

namespace App\Http\Requests\DailyItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebStoreDailyItemRequest extends FormRequest
{
    /** Keeps these errors apart from the edit modal's errors on the same page. */
    protected $errorBag = 'addItem';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', Rule::exists('items', 'id')->where('is_active', true)],
            'price' => ['required', 'numeric', 'min:0', 'max:99999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Choose an item.',
            'item_id.exists' => 'That item can\'t be added to the menu.',
            'price.required' => 'Enter a price.',
            'price.numeric' => 'The price must be a number.',
            'price.min' => 'The price can\'t be negative.',
            'price.max' => 'That price is too high.',
        ];
    }
}

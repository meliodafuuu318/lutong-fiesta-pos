<?php

namespace App\Http\Requests\DailyItem;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WebStoreManyDailyItemRequest extends FormRequest
{
    /** Same bag as the single-item form: both live in the same modal. */
    protected $errorBag = 'addItem';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_ids' => ['required', 'array', 'min:1', 'max:200'],
            'item_ids.*' => ['distinct', Rule::exists('items', 'id')->where('is_active', true)],
        ];
    }

    public function messages(): array
    {
        return [
            'item_ids.required' => 'Select at least one item.',
            'item_ids.min' => 'Select at least one item.',
            'item_ids.max' => 'You can add up to 200 items at once.',
            'item_ids.*.exists' => 'One of the selected items can\'t be added to the menu.',
            'item_ids.*.distinct' => 'An item was selected more than once.',
        ];
    }
}

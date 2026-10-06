<?php

namespace App\Http\Requests\Item;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;
use Illuminate\Validation\Rule;

class IndexItemRequest extends FormRequest
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
            'search' => ['sometimes', 'nullable', 'string'],
            'category' => ['sometimes', 'nullable', 'string', Rule::in(Category::pluck('name')->toArray())],
            'status' => ['sometimes', 'nullable', 'in:ACTIVE,INACTIVE'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'page' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'sort_by' => ['sometimes', 'nullable', 'string', 'in:name,default_price,created_at'],
            'sort_order' => ['sometimes', 'nullable', 'string', 'in:asc,desc'],
        ];
    }
}

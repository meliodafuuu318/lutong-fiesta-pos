<?php

namespace App\Http\Requests\DailyItem;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;
use Illuminate\Validation\Rule;

class IndexDailyItemRequest extends FormRequest
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
            'category' => ['sometimes', 'nullable', 'string', Rule::in(Category::pluck('name')->toArray())],
        ];
    }
}

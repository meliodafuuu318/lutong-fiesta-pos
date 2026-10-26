<?php

namespace App\Http\Requests\Expense;

/**
 * The add expense modal sends the same fields as the API, so it follows the
 * API's rules; only the error bag and the messages are different.
 */
class WebStoreExpenseRequest extends StoreExpenseRequest
{
    /** Keeps these errors apart from the filter form and other dialogs. */
    protected $errorBag = 'expenseForm';

    public function messages(): array
    {
        return [
            'amount.required' => 'Enter the amount.',
            'amount.numeric' => 'The amount must be a number.',
            'amount.gt' => 'The amount must be more than zero.',
            'amount.max' => 'That amount is too large.',
            'description.required' => 'Enter a description.',
            'description.max' => 'The description is too long.',
        ];
    }
}

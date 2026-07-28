<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'adjustment' => ['nullable', 'integer'],
            'stock' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

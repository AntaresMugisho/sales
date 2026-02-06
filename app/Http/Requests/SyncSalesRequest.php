<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncSalesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'sales' => ['required', 'array', 'min:1'],
            'sales.*.id' => ['required', 'uuid'],
            'sales.*.products' => ['required', 'array', 'min:1'],
            'sales.*.products.*.product_id' => ['required', 'exists:products,id'],
            'sales.*.products.*.quantity' => ['required', 'integer', 'min:1'],
            'sales.*.status' => ['sometimes', 'in:pending,completed,cancelled'],
            'sales.*.created_at_client' => ['sometimes', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSaleRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,completed,cancelled'],
        ];
    }

    // public function messages(): array
    // {
    //     return [
    //         'status.in' => 'Status must be pending, completed, or cancelled',
    //     ];
    // }
}

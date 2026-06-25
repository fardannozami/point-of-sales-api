<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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
            /** @example Kopi Susu */
            'name' => 'required|string|max:255',
            /** @example SKU-001 */
            'sku' => 'required|string|unique:products,sku|max:255',
            /** @example 15000 */
            'price' => 'required|numeric|min:0',
            /** @example 100 */
            'stock' => 'required|integer|min:0',
        ];
    }
}

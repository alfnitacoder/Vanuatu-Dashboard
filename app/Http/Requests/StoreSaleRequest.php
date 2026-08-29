<?php

namespace App\Http\Requests;

use App\Support\Tender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.name' => ['nullable', 'string', 'max:80'],
            'lines.*.qty' => ['required', 'integer', 'min:1', 'max:9999'],
            'lines.*.unit_price_vuv' => ['required', 'integer', 'min:0', 'max:10000000'],
            'tender' => ['required', Rule::in(Tender::values())],
            'tendered_vuv' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ];
    }

    /**
     * @return list<array{name?: string|null, qty: int, unit_price_vuv: int}>
     */
    public function lines(): array
    {
        return array_values(array_map(function (array $line) {
            return [
                'name' => $line['name'] ?? null,
                'qty' => (int) $line['qty'],
                'unit_price_vuv' => (int) $line['unit_price_vuv'],
            ];
        }, $this->validated('lines')));
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'shop_id',
    'user_id',
    'client_sale_id',
    'number',
    'receipt_no',
    'tender',
    'tendered_vuv',
    'change_vuv',
    'subtotal',
    'vat',
    'total',
    'receipt_text',
])]
class Sale extends Model
{
    /**
     * @return BelongsTo<Shop, $this>
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SaleLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(SaleLine::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'client_sale_id' => $this->client_sale_id,
            'receipt_no' => $this->receipt_no,
            'number' => $this->number,
            'tender' => $this->tender,
            'tendered_vuv' => $this->tendered_vuv,
            'change_vuv' => $this->change_vuv,
            'subtotal' => $this->subtotal,
            'net' => $this->subtotal,
            'vat' => $this->vat,
            'vat_vuv' => $this->vat,
            'total' => $this->total,
            'receipt_text' => $this->receipt_text,
            'created_at' => $this->created_at?->timezone('Pacific/Efate')->toIso8601String(),
            'lines' => $this->lines->map(fn (SaleLine $line) => [
                'name' => $line->name,
                'qty' => $line->qty,
                'unit_price_vuv' => $line->unit_price_vuv,
                'line_total_vuv' => $line->line_total_vuv,
            ])->values()->all(),
        ];
    }
}

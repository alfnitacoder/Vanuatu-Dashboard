<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Support\ReceiptText;
use App\Support\Tender;
use App\Support\Vat;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SaleRecorder
{
    /**
     * @param  list<array{name?: string|null, qty: int, unit_price_vuv: int}>  $lines
     */
    public function record(
        Shop $shop,
        User $user,
        array $lines,
        string $tender,
        string $clientSaleId,
        ?int $tenderedVuv = null,
    ): Sale {
        return DB::transaction(function () use ($shop, $user, $lines, $tender, $clientSaleId, $tenderedVuv) {
            $shop = Shop::query()->lockForUpdate()->findOrFail($shop->id);

            $existing = $shop->sales()
                ->where('client_sale_id', $clientSaleId)
                ->with('lines')
                ->first();
            if ($existing !== null) {
                return $existing;
            }

            $prepared = [];
            $total = 0;
            foreach ($lines as $line) {
                $qty = (int) $line['qty'];
                $unit = (int) $line['unit_price_vuv'];
                $lineTotal = $qty * $unit;
                $total += $lineTotal;
                $prepared[] = [
                    'name' => isset($line['name']) && $line['name'] !== '' ? (string) $line['name'] : null,
                    'qty' => $qty,
                    'unit_price_vuv' => $unit,
                    'line_total_vuv' => $lineTotal,
                ];
            }

            $split = Vat::split($total);
            $isCash = $tender === Tender::CASH;
            $tendered = $tenderedVuv ?? $total;
            if ($isCash && $tenderedVuv === null) {
                throw ValidationException::withMessages([
                    'tendered_vuv' => 'Tendered amount is required for cash.',
                ]);
            }
            if ($isCash && $tendered < $total) {
                throw ValidationException::withMessages([
                    'tendered_vuv' => 'Tendered amount must cover the inclusive total.',
                ]);
            }

            $next = (int) $shop->sales()->max('number') + 1;
            $receiptNo = 'INV-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);

            $sale = $shop->sales()->create([
                'user_id' => $user->id,
                'client_sale_id' => $clientSaleId,
                'number' => $next,
                'receipt_no' => $receiptNo,
                'tender' => $tender,
                'tendered_vuv' => $tendered,
                'change_vuv' => $isCash ? $tendered - $total : 0,
                'subtotal' => $split['subtotal'],
                'vat' => $split['vat'],
                'total' => $split['total'],
                'receipt_text' => '',
            ]);

            foreach ($prepared as $row) {
                $sale->lines()->create($row);
            }

            $sale->load('lines');
            $sale->receipt_text = ReceiptText::build($sale);
            $sale->save();

            return $sale;
        });
    }
}

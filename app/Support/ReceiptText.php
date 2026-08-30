<?php

namespace App\Support;

use App\Models\Sale;
use Carbon\CarbonInterface;

/**
 * SMS-pasteable plain text. Integers only. TOTAL then VAT note.
 *
 * STUA #142
 * 2000
 * 800
 * 650
 * TOTAL 3230 VUV
 * VAT included 421
 * Kas 4000
 * Senis 770
 * 30 Aug 10:42
 *
 * VAT is an extracted note under TOTAL. It is not a fourth item and
 * is not added into total.
 */
final class ReceiptText
{
    public static function build(Sale $sale): string
    {
        $lines = ['STUA #'.$sale->number];

        foreach ($sale->lines as $line) {
            $lines[] = (string) $line->line_total_vuv;
        }

        $lines[] = 'TOTAL '.$sale->total.' VUV';
        $lines[] = 'VAT included '.$sale->vat;

        $tendered = $sale->tendered_vuv ?? $sale->total;
        $change = $tendered - $sale->total;
        $lines[] = Tender::receiptLabel($sale->tender).' '.$tendered;
        $lines[] = 'Senis '.$change;

        $when = $sale->created_at instanceof CarbonInterface
            ? $sale->created_at->timezone('Pacific/Efate')
            : now('Pacific/Efate');
        $lines[] = $when->format('j M H:i');

        return implode("\n", $lines);
    }
}

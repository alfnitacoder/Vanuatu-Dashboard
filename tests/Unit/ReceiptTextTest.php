<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\SaleLine;
use App\Support\ReceiptText;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReceiptTextTest extends TestCase
{
    public function test_sms_body_is_total_then_vat_incl_note(): void
    {
        $sale = new Sale([
            'number' => 142,
            'tender' => 'cash',
            'tendered_vuv' => 4000,
            'change_vuv' => 550,
            'subtotal' => 3000,
            'vat' => 450,
            'total' => 3450,
        ]);
        $sale->created_at = Carbon::parse('2026-08-30 10:42:00', 'Pacific/Efate');
        $sale->setRelation('lines', collect([
            new SaleLine(['line_total_vuv' => 2000]),
            new SaleLine(['line_total_vuv' => 800]),
            new SaleLine(['line_total_vuv' => 650]),
        ]));

        $text = ReceiptText::build($sale);

        $this->assertSame(
            "STUA #142\n2000\n800\n650\nTOTAL 3450 VUV\nVAT incl. 450\nKas 4000\nSenis 550\n30 Aug 10:42",
            $text
        );
        $this->assertStringNotContainsString('VAT 15%', $text);
    }
}

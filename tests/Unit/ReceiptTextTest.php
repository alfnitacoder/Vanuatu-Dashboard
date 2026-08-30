<?php

namespace Tests\Unit;

use App\Models\Sale;
use App\Models\SaleLine;
use App\Support\ReceiptText;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReceiptTextTest extends TestCase
{
    public function test_sms_body_is_total_then_vat_included_note(): void
    {
        $sale = new Sale([
            'number' => 142,
            'tender' => 'cash',
            'tendered_vuv' => 4000,
            'change_vuv' => 770,
            'subtotal' => 2809,
            'vat' => 421,
            'total' => 3230,
        ]);
        $sale->created_at = Carbon::parse('2026-08-30 10:42:00', 'Pacific/Efate');
        $sale->setRelation('lines', collect([
            new SaleLine(['line_total_vuv' => 2400]),
            new SaleLine(['line_total_vuv' => 150]),
            new SaleLine(['line_total_vuv' => 680]),
        ]));

        $text = ReceiptText::build($sale);

        $this->assertSame(
            "STUA #142\n2400\n150\n680\nTOTAL 3230 VUV\nVAT included 421\nKas 4000\nSenis 770\n30 Aug 10:42",
            $text
        );
        $this->assertStringNotContainsString('3715', $text);
        $this->assertStringNotContainsString('485', $text);
        $this->assertTrue(strpos($text, 'TOTAL 3230 VUV') < strpos($text, 'VAT included 421'));
    }
}

<?php

namespace Tests\Unit;

use App\Support\Vat;
use PHPUnit\Framework\TestCase;

class VatTest extends TestCase
{
    public function test_extracts_inclusive_vat_from_design_gross_example(): void
    {
        $this->assertSame(24313, Vat::extract(186400));
    }

    public function test_demo_lines_2000_800_650(): void
    {
        $split = Vat::split(3450);

        $this->assertSame(3450, $split['total']);
        $this->assertSame(450, $split['vat']);
        $this->assertSame(3000, $split['subtotal']);
    }

    public function test_does_not_use_exclusive_add_on(): void
    {
        $this->assertNotSame((int) round(3230 * 15 / 100), Vat::extract(3230));
        $this->assertSame(421, Vat::extract(3230));
    }

    public function test_exact_115_is_15_vat(): void
    {
        $this->assertSame(15, Vat::extract(115));
        $this->assertSame([
            'subtotal' => 100,
            'vat' => 15,
            'total' => 115,
        ], Vat::split(115));
    }
}

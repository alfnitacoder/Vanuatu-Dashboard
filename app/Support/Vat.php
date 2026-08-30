<?php

namespace App\Support;

/**
 * Vanuatu VAT 15% extracted from a VAT-inclusive integer VUV total.
 *
 * vat = round(total * 15 / 115)
 * subtotal (ex VAT) = total - vat
 *
 * Do not use exclusive add-on tax (subtotal * 15 / 100).
 */
final class Vat
{
    public const RATE_NUMERATOR = 15;

    public const INCLUSIVE_DENOMINATOR = 115;

    public static function extract(int $inclusiveTotalVuv): int
    {
        return (int) round($inclusiveTotalVuv * self::RATE_NUMERATOR / self::INCLUSIVE_DENOMINATOR);
    }

    /**
     * @return array{subtotal: int, vat: int, total: int}
     */
    public static function split(int $inclusiveTotalVuv): array
    {
        $vat = self::extract($inclusiveTotalVuv);

        return [
            'subtotal' => $inclusiveTotalVuv - $vat,
            'vat' => $vat,
            'total' => $inclusiveTotalVuv,
        ];
    }
}

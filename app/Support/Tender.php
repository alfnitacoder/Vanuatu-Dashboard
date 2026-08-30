<?php

namespace App\Support;

final class Tender
{
    public const CASH = 'cash';

    public const CARD = 'card';

    public const MVATU = 'mvatu';

    public const MYCASH = 'mycash';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [self::CASH, self::CARD, self::MVATU, self::MYCASH];
    }

    public static function receiptLabel(string $tender): string
    {
        return match ($tender) {
            self::CASH => 'Kas',
            self::CARD => 'Kad',
            self::MVATU => 'M-Vatu',
            self::MYCASH => 'MyCash',
            default => $tender,
        };
    }

    public static function bilingual(string $tender): string
    {
        return match ($tender) {
            self::CASH => 'Cash / Kas',
            self::CARD => 'Card / Kad',
            self::MVATU => 'M-Vatu',
            self::MYCASH => 'MyCash',
            default => $tender,
        };
    }
}

<?php

namespace App\Support;

final class Money
{
    public static function format(int $vuv): string
    {
        return number_format($vuv, 0, '.', ',').' VUV';
    }
}

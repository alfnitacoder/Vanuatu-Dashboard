<?php

namespace App\Support;

/**
 * Minimal single-page text PDF. Secondary to SMS receipt_text.
 */
final class ReceiptPdf
{
    public static function render(string $body): string
    {
        $content = "BT\n/F1 11 Tf\n50 780 Td\n";
        $first = true;
        foreach (preg_split("/\r\n|\n|\r/", $body) as $line) {
            $safe = self::escape($line);
            if ($first) {
                $content .= '('.$safe.") Tj\n";
                $first = false;
            } else {
                $content .= '0 -16 Td ('.$safe.") Tj\n";
            }
        }
        $content .= "ET\n";

        $objects = [];
        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $objects[] = "<< /Type /Pages /Kids [3 0 R] /Count 1 >>";
        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>";
        $objects[] = "<< /Length ".strlen($content)." >>\nstream\n".$content."endstream";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer << /Size ".(count($objects) + 1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xref."\n%%EOF\n";

        return $pdf;
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

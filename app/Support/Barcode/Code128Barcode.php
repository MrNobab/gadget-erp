<?php

namespace App\Support\Barcode;

class Code128Barcode
{
    private const START_B = 104;
    private const START_C = 105;
    private const STOP = 106;

    private const PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    public function canEncode(string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return false;
        }

        return preg_match('/^[\x20-\x7E]+$/', $value) === 1;
    }

    public function svg(string $value, int $height = 44): string
    {
        $value = trim($value);

        if (! $this->canEncode($value)) {
            return '';
        }

        $codes = $this->codes($value);
        $quietZone = 10;
        $x = $quietZone;
        $bars = '';

        foreach ($codes as $code) {
            $pattern = self::PATTERNS[$code];

            foreach (str_split($pattern) as $index => $width) {
                $moduleWidth = (int) $width;

                if ($index % 2 === 0) {
                    $bars .= sprintf(
                        '<rect x="%d" y="0" width="%d" height="%d" fill="#000"/>',
                        $x,
                        $moduleWidth,
                        $height
                    );
                }

                $x += $moduleWidth;
            }
        }

        $totalWidth = $x + $quietZone;

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %d %d" preserveAspectRatio="none" role="img" aria-label="Barcode" shape-rendering="crispEdges">%s</svg>',
            $totalWidth,
            $height,
            $bars
        );
    }

    private function codes(string $value): array
    {
        if (ctype_digit($value) && strlen($value) % 2 === 0) {
            return $this->codesForNumericPairs($value);
        }

        $codes = [self::START_B];
        $checksum = self::START_B;

        foreach (str_split($value) as $position => $character) {
            $code = ord($character) - 32;
            $codes[] = $code;
            $checksum += $code * ($position + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = self::STOP;

        return $codes;
    }

    private function codesForNumericPairs(string $value): array
    {
        $codes = [self::START_C];
        $checksum = self::START_C;
        $position = 1;

        foreach (str_split($value, 2) as $pair) {
            $code = (int) $pair;
            $codes[] = $code;
            $checksum += $code * $position;
            $position++;
        }

        $codes[] = $checksum % 103;
        $codes[] = self::STOP;

        return $codes;
    }
}

<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RupiahInput
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_string($value) && ! is_int($value) && ! is_float($value)) {
            throw new \InvalidArgumentException('Format nominal tidak valid.');
        }
        if (strlen((string) $value) > 30) {
            throw new \InvalidArgumentException('Nominal terlalu panjang.');
        }
        $text = preg_replace('/^Rp\s*/i', '', trim((string) $value));
        if (preg_match('/^-?\d+(?:\.\d{1,2})?$/D', $text)) {
            return $text;
        }
        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d{1,2})?$/D', $text)) {
            return str_replace(',', '.', str_replace('.', '', $text));
        }
        if (preg_match('/^\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?$/D', $text)) {
            return str_replace(',', '', $text);
        }
        if (preg_match('/^\d+[,.]\d{1,2}$/D', $text)) {
            return str_replace(',', '.', $text);
        }
        throw new \InvalidArgumentException('Format nominal tidak valid.');
    }

    public static function prepare(Request $request, string $field): void
    {
        try {
            $request->merge([$field => self::normalize($request->input($field))]);
        } catch (\InvalidArgumentException) {
            throw ValidationException::withMessages([$field => 'Gunakan contoh 250000, 250.000, atau 250.000,00. Maksimal dua angka desimal; 250,00000 bukan format rupiah yang valid.']);
        }
    }
}

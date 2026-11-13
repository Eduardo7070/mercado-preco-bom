<?php

namespace App\Actions\Operations;

use Illuminate\Validation\ValidationException;

class Quantity
{
    public static function milli(mixed $value): int
    {
        $text = is_float($value) ? number_format($value, 3, '.', '') : (string) $value;
        if (! preg_match('/^\d+(?:\.\d{1,3})?$/', $text)) {
            throw ValidationException::withMessages(['quantidade' => 'Informe uma quantidade válida com até 3 casas decimais.']);
        }
        [$whole,$decimal] = array_pad(explode('.', $text), 2, '');
        if (strlen(ltrim($whole, '0')) > 12) {
            throw ValidationException::withMessages(['quantidade' => 'Quantidade acima do limite de armazenamento.']);
        }

        return (int) $whole * 1000 + (int) str_pad($decimal, 3, '0');
    }

    public static function value(int $milli): float
    {
        return $milli / 1000;
    }

    public static function checkUnit(string $unit, mixed $quantity): void
    {
        if (in_array($unit, ['UN', 'CX']) && self::milli($quantity) % 1000 !== 0) {
            throw ValidationException::withMessages(['quantidade' => 'Produtos UN ou CX exigem quantidade inteira.']);
        }
    }

    public static function cents(mixed $price): int
    {
        return (int) round((float) $price * 100);
    }

    public static function line(int $price, mixed $quantity): int
    {
        $milli = self::milli($quantity);
        $whole = intdiv($milli, 1000);
        $fraction = $milli % 1000;
        if ($price < 0 || ($price > 0 && $whole > intdiv(PHP_INT_MAX - 1000000000000, $price))) {
            throw ValidationException::withMessages(['items' => 'Valor acima do limite permitido.']);
        }

        return $price * $whole + intdiv($price * $fraction + 500, 1000);
    }
}

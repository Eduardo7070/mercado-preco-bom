<?php

namespace App\Actions\Operations;

use Illuminate\Validation\ValidationException;

class Fingerprint
{
    public static function make(array $data): string
    {
        unset($data['version'],$data['_token'],$data['_method']);
        $numeric = ['quantity', 'quantidade', 'deposito', 'gondola', 'amount', 'unit_cost', 'received', 'product_id', 'sale_item_id', 'supplier_id', 'customer_id', 'cash_session_id', 'draft_id', 'user_id', 'user', 'sale_id'];
        $normalize = function (mixed $value, ?string $key = null) use (&$normalize, $numeric): mixed {
            if (is_array($value)) {
                if (! array_is_list($value)) {
                    ksort($value);
                }
                $result = [];
                foreach ($value as $name => $item) {
                    $result[$name] = $normalize($item, is_string($name) ? $name : null);
                }

                return $result;
            }
            if (in_array($key, $numeric, true) && is_numeric($value)) {
                return number_format((float) $value, 3, '.', '');
            }

            return $value;
        };

        return hash('sha256', json_encode($normalize($data), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function assert(?string $stored, string $current): void
    {
        if (! $stored || ! hash_equals($stored, $current)) {
            throw ValidationException::withMessages(['operation_id' => 'Esta operação já foi utilizada com dados diferentes. Atualize a tela para iniciar uma nova operação.']);
        }
    }
}

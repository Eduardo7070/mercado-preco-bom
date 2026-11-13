<?php

namespace App\Actions\Administration;

use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveReference
{
    public function model(string $resource): string
    {
        return match ($resource) {
            'categorias' => Category::class,'clientes' => Customer::class,'fornecedores' => Supplier::class,'terminais' => CashRegister::class
        };
    }

    public function execute(string $resource, ?int $id, array $data, bool $delete = false): Model
    {
        return DB::transaction(function () use ($resource, $id, $data, $delete) {
            $class = $this->model($resource);
            $model = $id ? $class::whereKey($id)->lockForUpdate()->firstOrFail() : new $class;
            if ($delete) {
                $used = match ($resource) {
                    'categorias' => DB::table('products')->where('category_id', $model->id)->exists(),
                    'clientes' => DB::table('sales')->where('customer_id', $model->id)->exists(),
                    'fornecedores' => DB::table('purchases')->where('supplier_id', $model->id)->exists(),
                    'terminais' => DB::table('cash_sessions')->where('cash_register_id', $model->id)->exists(),
                };
                if ($used) {
                    throw ValidationException::withMessages(['record' => 'Cadastro possui histórico e não pode ser excluído. Utilize a inativação quando disponível.']);
                }
                $model->delete();

                return $model;
            }
            if ($resource === 'terminais' && $model->active_session_id) {
                throw ValidationException::withMessages(['name' => 'Feche o terminal antes de alterá-lo.']);
            }
            $model->fill($data);
            $model->save();
            if ($resource === 'categorias') {
                DB::table('products')->where('category_id', $model->id)->update(['categoria' => $model->name, 'updated_at' => now()]);
            }

            return $model;
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $seen = [];
        foreach (DB::table('products')->select('id', 'codigo', 'barras')->get() as $p) {
            foreach (array_unique(array_filter([$p->codigo, $p->barras], fn ($v) => $v !== null && $v !== '')) as $value) {
                if (isset($seen[$value]) && $seen[$value] !== $p->id) {
                    throw new RuntimeException('Identificador ambíguo entre produtos: '.$value.'. Corrija os identificadores antes de migrar.');
                }
                $seen[$value] = $p->id;
            }
        }
        Schema::table('users', fn (Blueprint $t) => $t->boolean('active')->default(true));
        foreach (['categories', 'customers', 'suppliers'] as $name) {
            Schema::create($name, function (Blueprint $t) use ($name) {
                $t->id();
                $t->string('name');
                if ($name === 'categories') {
                    $t->unique('name');
                }$t->boolean('active')->default(true);
                if ($name !== 'categories') {
                    $t->string('email')->nullable();
                    $t->string('phone', 30)->nullable();
                    $t->string('document', 30)->nullable()->unique();
                }$t->timestamps();
            });
        }
        Schema::table('products', function (Blueprint $t) {
            $t->decimal('deposito', 15, 3)->default(0)->change();
            $t->decimal('gondola', 15, 3)->default(0)->change();
            $t->decimal('custo', 12, 2)->default(0);
            $t->foreignId('category_id')->nullable()->constrained('categories')->restrictOnDelete();
        });
        Schema::create('product_identifiers', function (Blueprint $t) {
            $t->string('identifier', 191)->primary();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
        });
        foreach ($seen as $value => $id) {
            DB::table('product_identifiers')->insert(['identifier' => (string) $value, 'product_id' => $id]);
        }
        foreach (array_unique(array_merge(['Mercearia', 'Laticínios', 'Limpeza', 'Hortifruti', 'Bebidas'], DB::table('products')->pluck('categoria')->all())) as $name) {
            $id = DB::table('categories')->insertGetId(['name' => $name, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
            DB::table('products')->where('categoria', $name)->update(['category_id' => $id]);
        }
        Schema::table('stock_movements', function (Blueprint $t) {
            foreach (['quantidade', 'saldo_anterior', 'saldo_final'] as $column) {
                $t->decimal($column, 15, 3)->change();
            }
            $t->string('request_hash', 64)->nullable();
            $t->json('allocations')->nullable();
        });
        Schema::create('stock_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('local', 20);
            $t->string('lot');
            $t->date('expires_on')->nullable();
            $t->decimal('quantity', 15, 3)->default(0);
            $t->boolean('quarantine')->default(false);
            $t->timestamps();
            $t->index(['product_id', 'local']);
        });
        DB::table('products')->orderBy('id')->chunkById(200, function ($products) {
            foreach ($products as $p) {
                foreach (['deposito', 'gondola'] as $local) {
                    if ($p->$local > 0) {
                        DB::table('stock_batches')->insert(['product_id' => $p->id, 'local' => $local, 'lot' => 'LEGADO-'.$p->id, 'expires_on' => null, 'quantity' => $p->$local, 'quarantine' => (bool) $p->validade, 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
        });
        Schema::create('cash_registers', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->unsignedBigInteger('active_session_id')->nullable();
            $t->timestamps();
        });
        Schema::create('cash_sessions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('status', 15)->default('open');
            $t->unsignedBigInteger('opening_cents');
            $t->bigInteger('expected_cents')->nullable();
            $t->unsignedBigInteger('counted_cents')->nullable();
            $t->bigInteger('difference_cents')->nullable();
            $t->foreignId('closed_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->timestamp('closed_at')->nullable();
            $t->text('closing_note')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });
        Schema::create('cash_entries', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->string('request_hash', 64);
            $t->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('kind', 20);
            $t->bigInteger('amount_cents');
            $t->text('reason');
            $t->timestamps();
        });
        Schema::table('sales', function (Blueprint $t) {
            $t->string('request_hash', 64)->nullable();
            $t->foreignId('cash_session_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('customer_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('returned_cents')->default(0);
        });
        Schema::table('sale_items', function (Blueprint $t) {
            $t->decimal('quantity', 15, 3)->change();
            $t->decimal('returned_quantity', 15, 3)->default(0);
            $t->unsignedBigInteger('returned_cents')->default(0);
            $t->unsignedBigInteger('unit_cost_cents')->default(0);
            $t->string('category_name')->nullable();
        });
        foreach (DB::table('sales')->where('status', 'refunded')->get() as $sale) {
            DB::table('sales')->where('id', $sale->id)->update(['returned_cents' => $sale->total_cents]);
            DB::table('sale_items')->where('sale_id', $sale->id)->update(['returned_quantity' => DB::raw('quantity'), 'returned_cents' => DB::raw('total_cents')]);
        }
        Schema::create('sale_item_batches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $t->foreignId('stock_batch_id')->constrained()->restrictOnDelete();
            $t->decimal('quantity', 15, 3);
            $t->decimal('returned_quantity', 15, 3)->default(0);
        });
        Schema::create('sale_returns', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->string('request_hash', 64);
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('cash_session_id')->constrained()->restrictOnDelete();
            $t->text('reason');
            $t->unsignedBigInteger('amount_cents');
            $t->unsignedBigInteger('cost_cents')->default(0);
            $t->json('items');
            $t->timestamps();
        });
        Schema::create('sale_drafts', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('label');
            $t->string('customer_name')->nullable();
            $t->json('items');
            $t->string('status', 20)->default('suspended');
            $t->timestamps();
        });
        Schema::create('purchases', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->string('request_hash', 64);
            $t->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('document')->nullable();
            $t->unsignedBigInteger('total_cents');
            $t->json('items');
            $t->timestamps();
            $t->unique(['supplier_id', 'document']);
        });
        foreach (['Caixa 01', 'Caixa 02', 'Caixa 03'] as $name) {
            DB::table('cash_registers')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Esta migration contém histórico operacional. Restaure um backup para reverter, evitando perda de vendas e lotes.');
    }
};

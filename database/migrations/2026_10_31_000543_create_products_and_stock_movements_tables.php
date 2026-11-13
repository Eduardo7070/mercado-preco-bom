<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->nullable()->unique();
            $table->string('barras')->nullable()->unique();
            $table->string('nome');
            $table->string('categoria');
            $table->string('unidade', 5);
            $table->decimal('preco', 12, 2);
            $table->boolean('ativo')->default(true);
            $table->boolean('validade')->default(false);
            $table->unsignedInteger('deposito')->default(0);
            $table->unsignedInteger('gondola')->default(0);
            $table->timestamp('movimentado_em')->nullable();
            $table->timestamps();
            $table->index('nome');
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('operacao', 20);
            $table->string('local', 20);
            $table->unsignedInteger('quantidade');
            $table->unsignedInteger('saldo_anterior');
            $table->unsignedInteger('saldo_final');
            $table->string('documento')->nullable();
            $table->string('lote')->nullable();
            $table->date('validade')->nullable();
            $table->text('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('products');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $t) {
            $t->id();
            $t->uuid('operation_id')->unique();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('customer_name')->nullable();
            $t->string('terminal', 20)->default('Caixa 01');
            $t->string('payment_method', 20);
            $t->unsignedBigInteger('total_cents');
            $t->unsignedBigInteger('received_cents');
            $t->unsignedBigInteger('change_cents');
            $t->string('status', 20)->default('paid');
            $t->boolean('simulated')->default(true);
            $t->foreignId('refunded_by')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('refund_reason')->nullable();
            $t->timestamp('refunded_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'created_at']);
            $t->index('status');
        });
        Schema::create('sale_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('sale_id')->constrained()->restrictOnDelete();
            $t->foreignId('product_id')->constrained()->restrictOnDelete();
            $t->string('name');
            $t->string('code')->nullable();
            $t->string('unit', 5);
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price_cents');
            $t->unsignedBigInteger('total_cents');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};

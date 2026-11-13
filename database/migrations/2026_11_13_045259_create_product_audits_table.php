<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->string('product_name');
            $t->string('action', 30);
            $t->text('reason')->nullable();
            $t->json('before')->nullable();
            $t->json('after')->nullable();
            $t->timestamps();
        });
        $permission = DB::table('permissions')->where('slug', 'produtos.excluir')->value('id');
        if (! $permission) {
            $permission = DB::table('permissions')->insertGetId(['slug' => 'produtos.excluir', 'name' => 'Produtos / excluir', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (DB::table('profiles')->whereIn('slug', ['admin', 'gerente'])->pluck('id') as $profile) {
            DB::table('permission_profile')->insertOrIgnore(['profile_id' => $profile, 'permission_id' => $permission]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_audits');
        DB::table('permissions')->where('slug', 'produtos.excluir')->delete();
    }
};

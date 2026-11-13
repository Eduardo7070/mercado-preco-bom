<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $all = ['inicio', 'produtos.cadastrar', 'produtos.excluir', 'produtos.editar', 'produtos.inativar', 'produtos.consultar', 'estoque.entrada', 'estoque.saida', 'estoque.consultar', 'estoque.ajustar', 'caixa.finalizar', 'caixa.cancelar', 'caixa.pagamento', 'caixa.estornar', 'acessos.gerenciar'];
            foreach ($all as $slug) {
                Permission::firstOrCreate(['slug' => $slug], ['name' => ucfirst(str_replace('.', ' / ', $slug))]);
            }
            $matrix = ['admin' => $all, 'gerente' => array_values(array_diff($all, ['acessos.gerenciar'])), 'caixa' => ['inicio', 'produtos.consultar', 'caixa.finalizar', 'caixa.cancelar', 'caixa.pagamento'], 'estoquista' => ['inicio', 'produtos.consultar', 'estoque.entrada', 'estoque.saida', 'estoque.consultar', 'estoque.ajustar']];
            foreach ($matrix as $slug => $permissions) {
                $p = Profile::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]);
                if ($p->wasRecentlyCreated) {
                    $p->permissions()->sync(Permission::whereIn('slug', $permissions)->pluck('id'));
                }
                $email = $slug.'@mercadoprecobom.local';
                if (! User::where('email', $email)->exists()) {
                    $password = Str::password(20);
                    $u = new User;
                    $u->name = ucfirst($slug).' Mercado';
                    $u->email = $email;
                    $u->password = $password;
                    $u->profile_id = $p->id;
                    $u->save();
                    $this->command?->info($email.' | senha inicial: '.$password);
                }
            }
            User::whereNull('profile_id')->update(['profile_id' => Profile::where('slug', 'caixa')->value('id')]);
        });
    }
}

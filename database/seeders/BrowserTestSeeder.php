<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('testing') || config('database.default') !== 'sqlite' || ! str_contains((string) config('database.connections.sqlite.database'), 'mpb-ui-')) {
            throw new \RuntimeException('Seeder exclusivo do banco temporário de testes de interface.');
        }
        $this->call(DatabaseSeeder::class);
        $password = getenv('MPB_UI_PASSWORD');
        if (! $password) {
            throw new \RuntimeException('Senha temporária de teste não definida.');
        }
        foreach (User::all() as $user) {
            $user->password = $password;
            $user->save();
        }
    }
}

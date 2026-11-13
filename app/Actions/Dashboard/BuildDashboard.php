<?php

namespace App\Actions\Dashboard;

use App\Actions\Sales\SearchSales;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class BuildDashboard
{
    public function execute(User $user): array
    {
        $now = CarbonImmutable::now('America/Sao_Paulo');
        $global = in_array($user->profile?->slug, ['admin', 'gerente']);
        $data = [
            'greeting' => $now->hour < 12 ? 'Bom dia' : ($now->hour < 18 ? 'Boa tarde' : 'Boa noite'),
            'today' => $now->format('d/m/Y'),
            'profileName' => $user->profile?->name ?? 'Sem perfil',
            'scopeLabel' => $global ? 'Visão geral da loja' : 'Seu resumo de trabalho',
            'metrics' => [], 'sales' => null, 'payments' => null, 'lowStock' => null,
            'modules' => [], 'updatedAt' => $now->format('d/m/Y H:i'),
        ];
        $metric = function (string $label, string $value, string $description) use (&$data): void {
            $data['metrics'][] = compact('label', 'value', 'description');
        };
        $money = fn (int $cents): string => 'R$ '.number_format($cents / 100, 2, ',', '.');
        if ($user->can('caixa.finalizar')) {
            $query = app(SearchSales::class)->query($user);
            $today = (clone $query)->whereBetween('created_at', [$now->startOfDay()->utc(), $now->endOfDay()->utc()]);
            $paid = (clone $today)->whereIn('status', ['paid', 'partially_refunded']);
            $count = (clone $paid)->count();
            $total = (int) (clone $paid)->sum(DB::raw('total_cents - returned_cents'));
            $metric($global ? 'Vendas pagas hoje' : 'Minhas vendas pagas hoje', $money($total), $count.' vendas com valores líquidos. Pagamentos simulados.');
            $metric('Ticket médio de hoje', $money($count ? (int) round($total / $count) : 0), 'Média por venda paga no período.');
            $refunds = (clone $query)->whereIn('status', ['refunded', 'partially_refunded'])->whereBetween('refunded_at', [$now->startOfDay()->utc(), $now->endOfDay()->utc()])->count();
            $metric('Vendas com devolução hoje', number_format($refunds, 0, ',', '.'), 'Estornos realizados hoje no seu escopo de consulta.');
            $data['sales'] = (clone $query)->latest('id')->limit(5)->get();
            $data['payments'] = (clone $paid)->reorder()->select('payment_method')->selectRaw('COUNT(*) as sales_count, SUM(total_cents - returned_cents) as amount_cents')->groupBy('payment_method')->get();
        }
        if ($user->can('produtos.consultar')) {
            $count = Product::count();
            $active = Product::where('ativo', true)->count();
            $metric('Produtos cadastrados', number_format($count, 0, ',', '.'), $active.' ativos e '.($count - $active).' inativos.');
        }
        if ($user->can('estoque.consultar')) {
            $active = Product::where('ativo', true);
            $withStock = (clone $active)->whereRaw('(deposito + gondola) > 0')->count();
            $empty = (clone $active)->whereRaw('(deposito + gondola) = 0')->count();
            $metric('Produtos com estoque', number_format($withStock, 0, ',', '.'), 'Produtos ativos com saldo no depósito ou na gôndola.');
            $metric('Produtos sem estoque', number_format($empty, 0, ',', '.'), 'Produtos ativos com saldo total zerado.');
            $data['lowStock'] = (clone $active)->where('gondola', '<=', 5)->orderBy('gondola')->orderBy('nome')->limit(8)->get();
        }
        if ($user->can('acessos.gerenciar')) {
            $metric('Usuários cadastrados', number_format(User::count(), 0, ',', '.'), 'Contas cadastradas no sistema.');
        }
        $groups = [
            ['title' => 'Produtos', 'description' => 'Cadastro e consulta do catálogo.', 'links' => ['produtos.cadastrar' => 'Cadastrar produto', 'produtos.consultar' => 'Gerenciar produtos']],
            ['title' => 'Estoque', 'description' => 'Entradas, saídas e conferência dos saldos.', 'links' => ['estoque.entrada' => 'Entrada de mercadoria', 'estoque.saida' => 'Ajuste de estoque', 'estoque.consultar' => 'Consultar saldos']],
            ['title' => 'Frente de caixa', 'description' => 'Atendimento e histórico de vendas.', 'links' => ['caixa.finalizar' => 'Abrir frente de caixa', 'caixa.vendas' => 'Consultar vendas']],
            ['title' => 'Acessos', 'description' => 'Perfis e permissões dos usuários.', 'links' => ['acessos.index' => 'Gerenciar perfis e permissões']],
        ];
        foreach ($groups as $group) {
            $links = [];
            foreach ($group['links'] as $route => $label) {
                $permission = match ($route) {
                    'caixa.vendas' => 'caixa.finalizar','acessos.index' => 'acessos.gerenciar',default => $route
                };
                $allowed = match ($route) {
                    'produtos.consultar' => $user->canAny(['produtos.consultar', 'produtos.editar', 'produtos.inativar']),
                    'estoque.saida' => $user->canAny(['estoque.saida', 'estoque.ajustar']),
                    default => $user->can($permission),
                };
                if ($allowed) {
                    $links[] = ['route' => $route, 'label' => $label];
                }
            }
            if ($links) {
                $group['links'] = $links;
                $data['modules'][] = $group;
            }
        }

        return $data;
    }
}

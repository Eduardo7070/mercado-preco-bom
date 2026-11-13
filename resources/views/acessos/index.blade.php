@extends('layouts.mercado')
@section('content')
@if(session('status')) <p class="aviso" role="status">{{ session('status') }}</p> @endif
@if($errors->any()) <p class="aviso erro" role="alert">{{ $errors->first() }}</p> @endif
@php
    $profileData = $profiles->map(fn ($profile) => [
        'id' => $profile->id,
        'name' => $profile->name,
        'slug' => $profile->slug,
        'permissionIds' => $profile->permissions->pluck('id')->values(),
        'url' => route('acessos.profiles.update', $profile),
    ])->values();
    $groupNames = ['inicio' => 'Página inicial', 'produtos' => 'Produtos', 'estoque' => 'Estoque', 'caixa' => 'Frente de caixa', 'acessos' => 'Administração'];
    $permissionLabels = ['inicio' => 'Acessar início', 'produtos.cadastrar' => 'Cadastrar produtos', 'produtos.consultar' => 'Consultar produtos', 'produtos.editar' => 'Editar produtos', 'produtos.excluir' => 'Excluir produtos', 'produtos.inativar' => 'Ativar e inativar produtos', 'estoque.entrada' => 'Registrar entradas', 'estoque.saida' => 'Registrar saídas', 'estoque.consultar' => 'Consultar saldos e histórico', 'estoque.ajustar' => 'Ajustar e transferir saldos', 'caixa.finalizar' => 'Abrir caixa e consultar vendas', 'caixa.pagamento' => 'Confirmar pagamentos simulados', 'caixa.cancelar' => 'Cancelar itens', 'caixa.estornar' => 'Estornar vendas', 'acessos.gerenciar' => 'Gerenciar perfis e permissões'];
@endphp
<script type="application/json" id="perfis-dados">@json($profileData)</script>
<section class="painel perfis-painel" aria-labelledby="perfis-titulo">
    <div class="painel-titulo">
        <div><h2 id="perfis-titulo">Permissões por perfil</h2><p class="usuarios-descricao">Escolha um perfil para consultar ou ajustar seus acessos.</p></div>
        <span class="usuarios-contagem">{{ $profiles->count() }} perfis</span>
    </div>
    <div class="perfil-controle">
        <label class="campo perfil-seletor">Perfil de acesso
            <select id="perfil-selecionado">
                @foreach($profiles as $profile)<option value="{{ $profile->id }}">{{ $profile->name }}</option>@endforeach
            </select>
        </label>
        <div class="perfil-resumo"><strong id="perfil-resumo-nome"></strong><span id="perfil-resumo-contagem"></span></div>
        <button type="button" id="abrir-permissoes" class="botao" aria-haspopup="dialog" aria-controls="modal-permissoes">Permissões</button>
    </div>
    <p id="permissoes-feedback" class="perfil-feedback" role="status" aria-live="polite"></p>
</section>

<dialog id="modal-permissoes" class="permissoes-modal" aria-labelledby="modal-permissoes-titulo" aria-describedby="modal-permissoes-descricao">
    <form id="form-permissoes" method="POST" data-server-form>
        @csrf
        @method('PUT')
        <header class="permissoes-cabecalho">
            <div><h2 id="modal-permissoes-titulo">Permissões do perfil</h2><p id="modal-permissoes-descricao">As alterações se aplicam a todos os usuários deste perfil.</p></div>
            <button type="button" class="fechar" id="fechar-permissoes" aria-label="Fechar permissões">×</button>
        </header>
        <div class="permissoes-corpo">
            <p id="perfil-protegido" class="perfil-protegido" hidden>O administrador possui acessos protegidos. Suas permissões podem ser consultadas, mas não alteradas.</p>
            <label class="campo permissao-busca">Buscar permissão<input type="search" id="buscar-permissao" placeholder="Ex.: estoque, editar, vendas…" autocomplete="off"></label>
            <div class="permissao-selecao"><span id="permissoes-contador" role="status" aria-live="polite"></span><div><button type="button" id="marcar-permissoes" class="link">Marcar visíveis</button><button type="button" id="desmarcar-permissoes" class="link">Desmarcar visíveis</button></div></div>
            <div class="permissoes-grupos">
                @foreach($permissions->groupBy(fn ($permission) => explode('.', $permission->slug)[0]) as $group => $items)
                    <fieldset class="permissao-grupo" data-permission-group>
                        <legend>{{ $groupNames[$group] ?? ucfirst($group) }}</legend>
                        <div class="permissao-opcoes">
                            @foreach($items as $permission)
                                <label class="permissao-item" data-permission-label>
                                    <input type="checkbox" name="permissions[]" value="{{ $permission->id }}">
                                    <span>{{ $permissionLabels[$permission->slug] ?? $permission->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @endforeach
            </div>
            <p id="permissoes-vazio" class="vazio" hidden>Nenhuma permissão encontrada para esta busca.</p>
            <p id="permissoes-erro" class="aviso erro" role="alert" hidden></p>
        </div>
        <footer class="permissoes-rodape"><span id="permissoes-alteracoes">Selecione os acessos permitidos.</span><div class="acoes"><button type="button" id="cancelar-permissoes" class="botao secundario">Fechar</button><button type="submit" id="salvar-permissoes" class="botao">Salvar permissões</button></div></footer>
    </form>
</dialog>
<section class="painel usuarios-perfis" aria-labelledby="usuarios-perfis-titulo">
    <div class="painel-titulo">
        <div>
            <h2 id="usuarios-perfis-titulo">Perfil dos usuários</h2>
            <p class="usuarios-descricao">Defina o acesso de cada pessoa ao sistema.</p>
        </div>
        <span class="usuarios-contagem">{{ $users->total() }} {{ $users->total() === 1 ? 'usuário' : 'usuários' }}</span>
    </div>
    <div class="usuarios-colunas" aria-hidden="true">
        <span>Usuário</span>
        <span>Perfil de acesso</span>
        <span>Ação</span>
    </div>
    <div class="usuarios-lista">
        @forelse($users as $user)
            <form method="POST" action="{{ route('acessos.users.update', $user) }}" data-server-form class="usuario-linha">
                @csrf
                @method('PUT')
                <div class="usuario-identidade">
                    <div class="usuario-nome">
                        <strong>{{ $user->name }}</strong>
                        @if($user->is(auth()->user()))
                            <span class="usuario-atual">Você</span>
                        @endif
                    </div>
                    <span class="usuario-email">{{ $user->email }}</span>
                </div>
                <div class="usuario-perfil">
                    <label class="usuario-perfil-label" for="perfil-{{ $user->id }}">Perfil de {{ $user->name }}</label>
                    <select id="perfil-{{ $user->id }}" name="profile_id" @disabled($user->is(auth()->user())) aria-describedby="{{ $user->is(auth()->user()) ? 'perfil-protegido-'.$user->id : 'perfil-ajuda-'.$user->id }}">
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}" @selected($user->profile_id === $profile->id)>{{ $profile->name }}</option>
                        @endforeach
                    </select>
                    @if($user->is(auth()->user()))
                        <small id="perfil-protegido-{{ $user->id }}">Seu próprio perfil é protegido.</small>
                    @else
                        <small id="perfil-ajuda-{{ $user->id }}">A alteração vale após salvar.</small>
                    @endif
                </div>
                <button type="submit" class="botao usuario-salvar" @disabled($user->is(auth()->user())) aria-label="Salvar perfil de {{ $user->name }}">Salvar perfil</button>
            </form>
        @empty
            <p class="usuarios-vazio">Nenhum usuário cadastrado.</p>
        @endforelse
    </div>
    @if($users->hasPages())
        <div class="usuarios-paginacao">{{ $users->links() }}</div>
    @endif
</section>
@endsection
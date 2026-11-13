<?php

namespace App\Http\Controllers;

use App\Actions\Administration\SaveReference;
use App\Http\Requests\ReferenceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReferenceController extends Controller
{
    private function authorizeResource(string $resource): void
    {
        Gate::authorize($resource === 'terminais' ? 'caixa.gerenciar' : $resource.'.gerenciar');
    }

    public function index(Request $r, string $resource, SaveReference $a): View
    {
        $this->authorizeResource($resource);
        $r->validate(['q' => ['nullable', 'string', 'max:191']]);
        $class = $a->model($resource);
        $query = $class::query();
        if ($r->filled('q')) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $r->string('q')).'%';
            $query->whereRaw("name LIKE ? ESCAPE '!'", [$term]);
        }
        $record = $r->filled('edit') ? $class::findOrFail($r->integer('edit')) : new $class;

        return view('cadastros.index', ['title' => ucfirst($resource), 'module' => 'cadastros', 'asset' => 'acessos', 'resource' => $resource, 'records' => $query->orderBy('name')->paginate(20)->withQueryString(), 'record' => $record]);
    }

    public function store(ReferenceRequest $r, string $resource, SaveReference $a): RedirectResponse
    {
        $a->execute($resource, null, $r->validated());

        return redirect()->route('cadastros.index', $resource)->with('status', 'Cadastro criado.');
    }

    public function update(ReferenceRequest $r, string $resource, int $record, SaveReference $a): RedirectResponse
    {
        $a->execute($resource, $record, $r->validated());

        return redirect()->route('cadastros.index', $resource)->with('status', 'Cadastro atualizado.');
    }

    public function destroy(ReferenceRequest $r, string $resource, int $record, SaveReference $a): RedirectResponse
    {
        $a->execute($resource, $record, [], true);

        return back()->with('status', 'Cadastro excluído.');
    }
}

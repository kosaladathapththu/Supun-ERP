<?php

namespace App\Http\Controllers;

use App\Http\Requests\MasterDataRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class MasterDataController extends Controller
{
    protected string $model;

    protected string $route;

    protected string $title;

    protected array $extraFields = [];

    public function index(Request $request): View
    {
        $query = ($this->model)::query()->where('company_id', $request->user()->company_id);
        if ($search = trim((string) $request->query('search'))) {
            $query->where(fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        }
        $records = $query->latest()->paginate(15)->withQueryString();

        return view('master-data.index', $this->viewData(compact('records')));
    }

    public function create(): View
    {
        return view('master-data.form', $this->viewData(['record' => new $this->model, 'parents' => $this->parents()]));
    }

    public function store(MasterDataRequest $request): RedirectResponse
    {
        $data = $this->data($request);
        $data['company_id'] = $request->user()->company_id;
        ($this->model)::create($data);

        return redirect()->route("{$this->route}.index")->with('success', "{$this->title} created successfully.");
    }

    public function edit($record): View
    {
        $record = ($this->model)::findOrFail($record);
        $this->authorizeRecord($record);

        return view('master-data.form', $this->viewData(compact('record') + ['parents' => $this->parents($record->id)]));
    }

    public function update(MasterDataRequest $request, $record): RedirectResponse
    {
        $record = ($this->model)::findOrFail($record);
        $this->authorizeRecord($record);
        $record->update($this->data($request));

        return redirect()->route("{$this->route}.index")->with('success', "{$this->title} updated successfully.");
    }

    protected function data(MasterDataRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return array_intersect_key($data, array_flip(array_merge(['code', 'name', 'is_active'], $this->extraFields)));
    }

    protected function parents(?int $exclude = null)
    {
        return collect();
    }

    protected function authorizeRecord($record): void
    {
        abort_unless($record->company_id === auth()->user()->company_id, 404);
    }

    protected function viewData(array $data): array
    {
        return $data + ['route' => $this->route, 'title' => $this->title, 'extraFields' => $this->extraFields];
    }
}

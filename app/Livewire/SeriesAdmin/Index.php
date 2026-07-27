<?php

namespace App\Livewire\SeriesAdmin;

use App\Enums\SeriesDocumentType;
use App\Models\Series;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public string $document_type = 'guia_remision';

    public string $code = '';

    public ?string $next_number = '1';

    protected function rules(): array
    {
        return [
            'document_type' => ['required', Rule::in(array_column(SeriesDocumentType::cases(), 'value'))],
            'code' => [
                'required', 'string', 'size:4', 'regex:/^[A-Z][A-Z0-9]{3}$/',
                Rule::unique('series', 'code')
                    ->where('document_type', $this->document_type)
                    ->where('environment', config('facturacion.environment')),
            ],
            'next_number' => ['required', 'integer', 'min:1'],
        ];
    }

    protected array $validationAttributes = [
        'document_type' => 'tipo de documento',
        'code' => 'serie',
        'next_number' => 'siguiente correlativo',
    ];

    public function openCreate(): void
    {
        $this->authorize('series.manage');
        $this->reset(['code']);
        $this->next_number = '1';
        $this->resetValidation();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('series.manage');
        $data = $this->validate();

        Series::query()->create([
            'document_type' => $data['document_type'],
            'code' => mb_strtoupper($data['code']),
            'next_number' => $data['next_number'],
            'environment' => config('facturacion.environment'),
        ]);

        $this->showForm = false;
        session()->now('ok', 'Serie registrada.');
    }

    public function toggleActive(int $seriesId): void
    {
        $this->authorize('series.manage');
        $series = Series::query()->findOrFail($seriesId);
        $series->update(['is_active' => ! $series->is_active]);
        session()->now('ok', $series->is_active ? 'Serie activada.' : 'Serie desactivada.');
    }

    public function render(): View
    {
        return view('livewire.series-admin.index', [
            'allSeries' => Series::query()->orderBy('document_type')->orderBy('code')->get(),
            'types' => SeriesDocumentType::cases(),
        ]);
    }
}

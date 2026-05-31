<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\InternshipPeriod;
use App\Services\PeriodConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeriodController extends Controller
{
    public function __construct(private readonly PeriodConfigurationService $configurations)
    {
    }

    public function index(): View
    {
        return view('management.periods.index', [
            'periods' => InternshipPeriod::query()->orderByDesc('is_active')->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $period = InternshipPeriod::query()->create($this->validated($request));
        $this->configurations->seedMissing($request->user()?->id);

        return redirect()->route('management.periods.edit', $period)->with('status', 'Periode berhasil ditambahkan.');
    }

    public function edit(InternshipPeriod $period): View
    {
        return view('management.periods.edit', compact('period'));
    }

    public function update(Request $request, InternshipPeriod $period): RedirectResponse
    {
        $period->update($this->validated($request, $period));

        return redirect()->route('management.periods.index')->with('status', 'Periode berhasil diperbarui.');
    }

    private function validated(Request $request, ?InternshipPeriod $period = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['nullable', 'string', 'max:30'],
            'semester' => ['nullable', 'string', 'max:30'],
            'batch' => ['nullable', 'string', 'max:80'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'is_locked' => ['nullable', 'boolean'],
        ]) + ['is_active' => false, 'is_locked' => false];
    }
}

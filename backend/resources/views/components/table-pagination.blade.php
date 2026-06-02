@props([
    'paginator',
])

@if ($paginator->hasPages() || $paginator->total() > 0)
    <div {{ $attributes->merge(['class' => 'silat-table-pagination']) }}>
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-2 text-sm text-gray-500 sm:flex-row sm:items-center">
                <span>
                    Menampilkan {{ $paginator->firstItem() ?? 0 }}-{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }} data
                </span>
                <form method="GET" class="flex items-center gap-2">
                    @foreach (request()->except(['page', 'per_page']) as $key => $value)
                        @if (! is_array($value))
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endif
                    @endforeach
                    <label for="per_page_{{ $paginator->getPageName() }}" class="text-gray-500">Tampilkan</label>
                    <select id="per_page_{{ $paginator->getPageName() }}" name="per_page" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                        @foreach ([10, 25, 50, 100] as $option)
                            <option value="{{ $option }}" @selected((int) request('per_page', $paginator->perPage()) === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    <span class="text-gray-500">per halaman</span>
                </form>
            </div>
            <div class="overflow-x-auto">
                {{ $paginator->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
@endif

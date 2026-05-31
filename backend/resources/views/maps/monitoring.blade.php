<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Peta Monitoring') }}
            </h2>
            @include('maps.partials.filters')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div
                    id="monitoring-map"
                    class="monpkl-map"
                    data-map-type="monitoring"
                    data-data-url="{{ route('maps.monitoring.data', array_merge(request()->query(), ['limit' => 1000])) }}"
                    data-map-config='@json($mapConfig)'
                ></div>
            </div>
        </div>
    </div>
</x-app-layout>

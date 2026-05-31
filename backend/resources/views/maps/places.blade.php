<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Peta Tempat PKL') }}
            </h2>
            @include('maps.partials.filters')
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div
                    id="places-map"
                    class="monpkl-map"
                    data-map-type="places"
                    data-data-url="{{ route('maps.places.data', request()->query()) }}"
                    data-map-config='@json($mapConfig)'
                ></div>
            </div>
        </div>
    </div>
</x-app-layout>

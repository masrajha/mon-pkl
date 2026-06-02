<button {{ $attributes->merge(['type' => 'submit', 'class' => 'silat-btn-danger focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>

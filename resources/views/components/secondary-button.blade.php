<button {{ $attributes->merge(['type' => 'button', 'class' => 'silat-btn-secondary focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2']) }}>
    {{ $slot }}
</button>

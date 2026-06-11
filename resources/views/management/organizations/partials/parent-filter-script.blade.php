<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-organization-type]').forEach((typeSelect) => {
            const form = typeSelect.closest('form');
            const parentSelect = form?.querySelector('[data-organization-parent]');
            if (! parentSelect) return;

            const options = Array.from(parentSelect.options);
            const sync = () => {
                const selectedType = typeSelect.value;
                let selectedStillValid = false;

                options.forEach((option) => {
                    const visible = option.dataset.parentFor === selectedType;
                    option.hidden = ! visible;
                    option.disabled = ! visible;

                    if (visible && option.selected) {
                        selectedStillValid = true;
                    }
                });

                if (! selectedStillValid) {
                    const firstVisible = options.find((option) => ! option.disabled);
                    parentSelect.value = firstVisible ? firstVisible.value : '';
                }
            };

            typeSelect.addEventListener('change', sync);
            sync();
        });
    });
</script>

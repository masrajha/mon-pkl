<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-period-date-sync]').forEach((form) => {
            const periodSelect = form.querySelector('[name="period_id"]');
            const startInput = form.querySelector('[name="start_date"]');
            const endInput = form.querySelector('[name="end_date"]');

            if (! periodSelect || ! startInput || ! endInput) {
                return;
            }

            periodSelect.addEventListener('change', () => {
                const option = periodSelect.selectedOptions[0];
                const startDate = option?.dataset.startDate;
                const endDate = option?.dataset.endDate;

                if (startDate) {
                    startInput.value = startDate;
                }

                if (endDate) {
                    endInput.value = endDate;
                }
            });
        });
    });
</script>

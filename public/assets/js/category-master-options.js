/* Populate shared client form dropdowns from Category Master. */
(function () {
    const selectGroups = {
        not_counting: 'not_counting',
        sent_to_client: 'sent_to_client',
        status_compliance: 'compliance_status',
        inforce_request_type: 'request_type',
        inforce_process_status: 'process_status',
        claim_update_status: 'claim_update_status',
        npw_premium_mode: 'premium_mode',
        npw_pending: 'npw_pending'
    };

    function populateCategorySelects() {
        const groups = window.categoryMasterOptions || {};

        Object.entries(selectGroups).forEach(([field, group]) => {
            const values = groups[group];
            if (!Array.isArray(values) || !values.length) return;

            document.querySelectorAll(`select[name="${field}"]`).forEach(select => {
                const oldValue = select.value;
                const firstOption = select.options[0];
                const placeholder = firstOption && (firstOption.value === '' || firstOption.disabled)
                    ? firstOption.cloneNode(true)
                    : null;

                select.replaceChildren();
                if (placeholder) select.append(placeholder);

                values.forEach(label => {
                    const option = new Option(label, field === 'not_counting'
                        ? (label.toLowerCase() === 'yes' ? '1' : '0')
                        : label);
                    select.append(option);
                });

                if ([...select.options].some(option => option.value === oldValue)) {
                    select.value = oldValue;
                }
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', populateCategorySelects, { once: true });
    } else {
        populateCategorySelects();
    }
})();

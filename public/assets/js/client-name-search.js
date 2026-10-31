(function ($) {
    function initializeClientNameSearch(select) {
        const $select = $(select);

        if ($select.hasClass('select2-hidden-accessible')) {
            return;
        }

        const modal = $select.closest('.modal')[0];
        const ajaxUrl = $select.data('ajax-url');
        const placeholder = $select.data('placeholder') || 'Search client name...';

        const select2Options = {
            width: '100%',
            placeholder: placeholder,
            allowClear: true,
            dropdownParent: modal ? $(modal) : $(document.body)
        };

        if (ajaxUrl) {
            select2Options.minimumInputLength = 0;
            select2Options.ajax = {
                url: ajaxUrl,
                dataType: 'json',
                delay: 250,
                cache: true,
                data: function (params) {
                    return {
                        q: params.term || '',
                        page: params.page || 1
                    };
                },
                processResults: function (data) {
                    return {
                        results: (data.results || []).map(function (client) {
                            return {
                                id: client.id || client.text,
                                text: client.text || client.name || client.id
                            };
                        }),
                        pagination: data.pagination || { more: false }
                    };
                }
            };
        }

        $select.select2(select2Options);

        // Auto-fetch/render options immediately when opened if user hasn't typed anything
        $select.on('select2:open', function () {
            const searchField = document.querySelector('.select2-container--open .select2-search__field');
            if (searchField && !searchField.value && ajaxUrl) {
                searchField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        });
    }

    function initializeClientNameSelects(context) {
        const $context = context ? $(context) : $(document);
        $context.find('#taskClientInput, #eventClientInput, .client-name-search').each(function () {
            initializeClientNameSearch(this);
        });
    }

    $(document).ready(function () {
        initializeClientNameSelects();
    });

    // Handle Bootstrap modals shown event (including #addTaskModal, #scheduleEventModal, etc.)
    $(document).on('shown.bs.modal', function (e) {
        const modal = e.target;
        $(modal).find('#taskClientInput, #eventClientInput, .client-name-search').each(function () {
            if ($(this).hasClass('select2-hidden-accessible')) {
                $(this).select2('destroy');
            }
            initializeClientNameSearch(this);
        });
    });
})(jQuery);

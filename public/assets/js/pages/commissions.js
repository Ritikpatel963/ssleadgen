/* Page script for commissions */
$(document).ready(function () {
            $('#commissionsTable').DataTable({
                responsive: false,
                pageLength: 10,
                order: [[7, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search commission payouts..."
                }
            });
        });

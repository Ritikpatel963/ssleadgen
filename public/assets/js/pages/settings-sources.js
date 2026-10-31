/* Page script for settings-sources */
let sourcesDataTable = null;

        function handleAddNewSource() {
            const name = $('#sourceNameInput').val().trim();
            const cat = $('#sourceCatInput').val().trim() || 'Custom Acquisition';

            if (!name) return;

            if (sourcesDataTable) {
                sourcesDataTable.row.add([
                    `<span class="fw-bold text-dark fs-13">${name}</span>`,
                    `<span class="fs-13 text-muted">${cat}</span>`,
                    `<span class="fs-13 fw-semibold">0</span>`,
                    `<span class="fs-13 fw-bold text-muted">0.0%</span>`,
                    `<span class="status-pill-inforce">Active</span>`,
                    `<div class="text-center"><button class="btn btn-sm btn-light py-1 px-2">Edit</button></div>`
                ]).draw(false);
            }

            $('#addSourceForm')[0].reset();
            const modalEl = document.getElementById('addSourceModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }

        $(document).ready(function () {
            sourcesDataTable = $('#sourcesSettingTable').DataTable({
                responsive: false,
                pageLength: 10,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search lead sources..."
                }
            });
        });

        document.getElementById('mobile-collapse')?.addEventListener('click', function () {
            document.getElementById('mainSidebar')?.classList.toggle('mob-navigation-active');
        });

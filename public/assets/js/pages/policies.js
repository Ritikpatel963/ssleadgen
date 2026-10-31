/* Page script for policies */
let policyDataTable = null;

        function handleIssueNewPolicy() {
            const holder = $('#policyHolderInput').val().trim();
            const polNum = $('#policyNumberInput').val().trim();
            const insurer = $('#policyInsurerInput').val();
            const sum = $('#policySumInput').val() || '500,000';
            const prem = $('#policyPremiumInput').val() || '2,400';

            if (!holder || !polNum) return;

            if (policyDataTable) {
                policyDataTable.row.add([
                    `<span class="fw-bold text-dark fs-13">${polNum}</span>`,
                    `<span class="fs-13">${holder}</span>`,
                    `<span class="fs-13">${insurer}</span>`,
                    `<span class="badge bg-soft-primary text-primary">Life & Disability</span>`,
                    `<span class="fs-13 fw-semibold">$${Number(sum).toLocaleString()}</span>`,
                    `<span class="fs-13 fw-semibold text-success">$${Number(prem).toLocaleString()}</span>`,
                    `<span class="fs-13 text-muted">18 Aug 2027</span>`,
                    `<span class="status-pill-inforce">Active</span>`,
                    `<div class="text-center"><button class="btn btn-sm btn-light py-1 px-2">Manage</button></div>`
                ]).draw(false);
            }

            $('#newPolicyForm')[0].reset();
            const modalEl = document.getElementById('newPolicyModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }

        $(document).ready(function () {
            policyDataTable = $('#policyDirectoryTable').DataTable({
                responsive: false,
                pageLength: 10,
                paging: false,
                lengthChange: false,
                info: false,
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Search policy records..."
                }
            });
        });

        document.getElementById('mobile-collapse')?.addEventListener('click', function () {
            document.getElementById('mainSidebar')?.classList.toggle('mob-navigation-active');
        });

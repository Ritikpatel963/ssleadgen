<!-- Modal: Reset Password -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-key me-2"></i> Reset Advisor Password</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form id="resetPasswordForm"
                    onsubmit="event.preventDefault(); alert('Password updated successfully!'); $('#resetPasswordModal').modal('hide');">
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="Minimum 8 characters" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold fs-13 text-dark">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Global Alert Modal (Replaces browser alert()) -->
    <div class="modal fade" id="globalAlertModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="avatar-text avatar-lg bg-soft-primary text-primary rounded-circle mx-auto mb-3"
                        style="width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="feather-info fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1" id="globalAlertTitle">Notification</h5>
                    <p class="text-muted fs-13 mb-4" id="globalAlertMessage"></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-primary btn-sm px-4 fw-bold" data-bs-dismiss="modal">OK</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Global Confirm Modal (Replaces browser confirm() for forms) -->
    <div class="modal fade" id="globalConfirmModal" tabindex="-1" aria-hidden="true" style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="avatar-text avatar-lg bg-soft-warning text-warning rounded-circle mx-auto mb-3"
                        style="width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="feather-alert-triangle fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Confirmation Required</h5>
                    <p class="text-muted fs-13 mb-4" id="globalConfirmMessage">Are you sure?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-warning btn-sm px-4 fw-bold" id="globalConfirmBtn">Yes, Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@push('scripts')
<script>
    // Overriding the default browser alert with our custom Bootstrap Modal
    window._originalAlert = window.alert;
    window.alert = function(message) {
        $('#globalAlertMessage').text(message);
        var modal = new bootstrap.Modal(document.getElementById('globalAlertModal'));
        modal.show();
    };

    // Global Confirm for Form Submissions
    window.confirmFormSubmit = function(event, message, formElement) {
        event.preventDefault();
        $('#globalConfirmMessage').text(message);
        var modal = new bootstrap.Modal(document.getElementById('globalConfirmModal'));
        
        $('#globalConfirmBtn').off('click').on('click', function() {
            modal.hide();
            formElement.submit();
        });
        
        modal.show();
    };
</script>
@endpush

    <!-- Modal: Logout Confirmation -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-body p-4 text-center">
                    <div class="avatar-text avatar-lg bg-soft-danger text-danger rounded-circle mx-auto mb-3"
                        style="width: 50px; height: 50px; display: inline-flex; align-items: center; justify-content: center;">
                        <i class="feather-log-out fs-3"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Confirm Logout</h5>
                    <p class="text-muted fs-13 mb-4">Are you sure you want to log out of your session?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="{{ route('logout') }}" id="logout-form">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm px-4 fw-bold">Yes, Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Import Results -->
    <div class="modal fade" id="importResultsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-success);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-check-circle me-2"></i> Import Completed</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="importResultsSummary" class="mb-4"></div>
                    <div id="importResultsTableContainer" class="d-none">
                        <h6 class="fw-bold text-dark mb-2">Unsuccessful Rows</h6>
                        <div class="table-responsive" style="max-height: 55vh; overflow: auto;">
                            <table class="table table-bordered table-striped fs-12 mb-0" style="min-width: 760px;">
                                <thead class="bg-light" style="position: sticky; top: 0; z-index: 1;">
                                    <tr id="importResultsTableHead"></tr>
                                </thead>
                                <tbody id="importResultsTableBody">
                                    <!-- Rendered dynamically via JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Dashboard Search Results -->
    <div class="modal fade" id="dashboardSearchModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header text-white" style="background-color: var(--color-navy-dark);">
                    <h5 class="modal-title text-white mb-0"><i class="feather-search me-2"></i> Search Results</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="dashboardSearchModalBody">
                    <!-- Rendered dynamically via JS -->
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

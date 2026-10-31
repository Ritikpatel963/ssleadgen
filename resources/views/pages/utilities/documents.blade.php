@extends('layouts.app')
@section('title', 'Document Vault')
@section('content')
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div>
            <h4 class="fw-bold text-dark mb-1">Document Vault &amp; Secure Sharing</h4>
            <p class="text-muted fs-13 mb-0">Drag and drop client disclosure statements, policy schedules, or medical
                records to generate shareable links.</p>
        </div>
    </div>

    <form id="documentUploadForm" action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="card-widget p-4 text-center border-dashed border-2 border-primary"
            style="background: rgba(0, 168, 181, 0.03); border-style: dashed !important; border-radius: 12px; cursor: pointer;"
            id="dropZoneContainer" onclick="document.getElementById('documentFileInput').click()">
            <div class="mb-3">
                <div class="avatar-text avatar-lg bg-soft-primary text-primary rounded-circle mx-auto fs-3"
                    style="width: 60px; height: 60px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="feather-upload-cloud"></i>
                </div>
            </div>
            <h5 class="fw-bold text-dark mb-1">Drag and Drop Files Here to Upload</h5>
            <p class="text-muted fs-13 mb-3">Or click below to browse policy PDFs, medical reports, or disclosure forms.</p>
            <input type="file" name="document" id="documentFileInput" class="d-none"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" onchange="handleDocumentUpload(this.form)">
            <button type="button" class="btn btn-primary px-4 fw-bold"><i class="feather-file-plus me-1"></i> Browse
                Files</button>
            @error('document')
            <div class="text-danger mt-2 fs-12">{{ $message }}</div> @enderror
        </div>
    </form>

    <form method="GET" action="{{ route('documents.index') }}" class="dash-filter-card">
        <div class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label for="documentFilterSearch" class="form-label fs-12 fw-semibold text-muted mb-1">Document or Client</label>
                <input id="documentFilterSearch" name="search" type="search" class="form-control fs-13" value="{{ request('search') }}" placeholder="Search by document or client">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="documentUploadedFrom" class="form-label fs-12 fw-semibold text-muted mb-1">Uploaded From</label>
                <input id="documentUploadedFrom" name="uploaded_from" type="date" class="form-control fs-13" value="{{ request('uploaded_from') }}">
            </div>
            <div class="col-lg-3 col-md-6">
                <label for="documentUploadedTo" class="form-label fs-12 fw-semibold text-muted mb-1">Uploaded To</label>
                <input id="documentUploadedTo" name="uploaded_to" type="date" class="form-control fs-13" value="{{ request('uploaded_to') }}">
            </div>
            <div class="col-lg-2 col-md-6 d-flex gap-2">
                <button class="btn btn-primary btn-sm fw-bold" type="submit"><i class="feather-filter me-1"></i> Filter</button>
                <a href="{{ route('documents.index') }}" class="btn btn-light btn-sm">Reset</a>
            </div>
        </div>
    </form>

    <!-- Document Directory Table -->
    <div class="card-widget">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="widget-title mb-0">Stored Advisory Documents</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle w-100" id="documentsTable" data-server-paginated>
                <thead>
                    <tr class="fs-12 text-muted text-uppercase fw-semibold" style="background: #F8FAFC;">
                        <th>Document Name</th>
                        <th>Associated Client</th>
                        <th>File Size</th>
                        <th>Upload Date</th>
                        <th>Shared Link</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td class="fw-bold text-dark fs-13">
                                <i class="feather-file-text text-danger me-2"></i> {{ $doc->name }}
                            </td>
                            <td class="fs-13 text-muted">{{ $doc->client_name ?? '—' }}</td>
                            <td class="fs-13 text-muted">{{ number_format($doc->size / 1048576, 2) }} MB</td>
                            <td class="fs-13 text-muted">{{ $doc->created_at->format('d M Y') }}</td>
                            <td>
                                <button class="btn btn-sm btn-light text-primary fw-semibold py-1 px-2"
                                    onclick="showCopyModal('{{ route('documents.share', $doc->token) }}')">
                                    <i class="feather-link me-1"></i> Copy Link
                                </button>
                            </td>
                            <td class="text-center">
                                <div class="action-kebab-wrapper">
                                    <button class="action-kebab-btn"><i class="feather-more-vertical"></i></button>
                                    <div class="action-kebab-dropdown">
                                        <a href="{{ route('documents.download', $doc->id) }}" class="action-kebab-item"><i
                                                class="feather-download text-primary me-2"></i> Download</a>
                                        <button type="button"
                                            class="action-kebab-item text-danger border-0 bg-transparent w-100 text-start"
                                            onclick="showDeleteModal({{ $doc->id }}, '{{ addslashes($doc->name) }}')">
                                            <i class="feather-trash-2 text-danger me-2"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No documents uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $documents->links('pagination::bootstrap-5') }}
        </div>
    </div>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="mb-3" style="font-size: 2.5rem;">🗑️</div>
                    <h6 class="fw-bold text-dark mb-1">Delete Document?</h6>
                    <p class="text-muted fs-13 mb-4" id="deleteDocName"></p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Cancel</button>
                        <form id="deleteDocForm" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger px-4 fw-bold">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy Link Success Modal -->
    <div class="modal fade" id="copyLinkModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="mb-3" style="font-size: 2.5rem;">🔗</div>
                    <h6 class="fw-bold text-dark mb-1">Link Copied!</h6>
                    <p class="text-muted fs-13 mb-3">The shareable document link has been copied to your clipboard.</p>
                    <div class="bg-light rounded p-2 mb-3">
                        <small class="text-muted text-break" id="copyLinkUrl" style="word-break: break-all;"></small>
                    </div>
                    <button type="button" class="btn btn-primary px-4 fw-bold" data-bs-dismiss="modal">Done</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('assets/js/dashboard-redesign.js?v=7.0') }}"></script>
        <script>
            function showDeleteModal(id, name) {
                document.getElementById('deleteDocName').textContent = 'This will permanently delete "' + name + '".';
                document.getElementById('deleteDocForm').action = '/utilities/documents/' + id;
                new bootstrap.Modal(document.getElementById('deleteDocModal')).show();
            }

            function showCopyModal(url) {
                navigator.clipboard.writeText(url).catch(() => { });
                document.getElementById('copyLinkUrl').textContent = url;
                new bootstrap.Modal(document.getElementById('copyLinkModal')).show();
            }
        </script>
    @endpush

    @push('scripts')
        <script src="{{ asset('assets/js/pages/documents.js') }}?v={{ filemtime(public_path('assets/js/pages/documents.js')) }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const dropZone = document.getElementById('dropZoneContainer');
                const fileInput = document.getElementById('documentFileInput');

                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, preventDefaults, false);
                });

                function preventDefaults(e) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                // Prevent default drag behaviors for the entire window to avoid accidental navigation
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    window.addEventListener(eventName, preventDefaults, false);
                });

                ['dragenter', 'dragover'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.style.background = 'rgba(0, 168, 181, 0.1)';
                    }, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    dropZone.addEventListener(eventName, () => {
                        dropZone.style.background = 'rgba(0, 168, 181, 0.03)';
                    }, false);
                });

                dropZone.addEventListener('drop', function (e) {
                    let dt = e.dataTransfer;
                    let files = dt.files;

                    if (files.length) {
                        fileInput.files = files;
                        handleDocumentUpload(fileInput.form);
                    }
                });
            });
        </script>
    @endpush
@endsection

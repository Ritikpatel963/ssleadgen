/* Page script for documents */
let docDataTable = null;

function initializeDocumentsTable() {
    if ($.fn.DataTable.isDataTable('#documentsTable')) {
        docDataTable = $('#documentsTable').DataTable();
        return;
    }

    docDataTable = $('#documentsTable').DataTable({
        responsive: false,
        pageLength: 10,
        paging: false,
        lengthChange: false,
        info: false,
        language: { search: '_INPUT_', searchPlaceholder: 'Search documents...' }
    });
}

function showDocumentMessage(message, type = 'success') {
    const title = type === 'success' ? 'Success' : 'Unable to complete action';
    window.alert(`${title}: ${message}`);
}

async function readDocumentResponse(response, fallbackMessage) {
    const body = await response.text();
    let data;

    try {
        data = JSON.parse(body);
    } catch (parseError) {
        // If JSON parsing fails, log the actual response for debugging
        console.error('JSON Parse Error:', parseError);
        console.error('Response body:', body.substring(0, 500)); // Log first 500 chars
        
        const detail = response.redirected
            ? 'Your session may have expired. Sign in again and retry.'
            : `The server returned an unexpected response (HTTP ${response.status}). ${body.includes('<!DOCTYPE') ? 'Server returned HTML instead of JSON.' : ''}`;
        throw new Error(detail);
    }

    if (!response.ok) throw new Error(data.message || fallbackMessage);
    return data;
}

function refreshDocumentsTable() {
    window.location.reload();
}

/**
 * Convert file to base64 (WAF bypass workaround)
 */
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
        reader.readAsDataURL(file);
    });
}

/**
 * Handle document upload using base64 encoding (bypasses WAF restrictions)
 */
async function handleDocumentUpload(form) {
    const fileInput = form.querySelector('input[type="file"]');

    if (!fileInput || !fileInput.files.length || form.dataset.uploading === 'true') return;

    const file = fileInput.files[0];
    const maxSize = 10 * 1024 * 1024; // 10MB

    // Validate file size
    if (file.size > maxSize) {
        showDocumentMessage('File size exceeds 10MB limit.', 'error');
        return;
    }

    // Validate file type
    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg'];
    const fileName = file.name.toLowerCase();
    const extension = fileName.substring(fileName.lastIndexOf('.') + 1);
    
    if (!allowedExtensions.includes(extension)) {
        showDocumentMessage('Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, PNG, JPG, JPEG', 'error');
        return;
    }

    form.dataset.uploading = 'true';
    const heading = document.querySelector('#dropZoneContainer h5');
    const originalHeading = heading?.textContent;
    if (heading) heading.textContent = 'Uploading, please wait...';

    try {
        // Convert file to base64
        const base64Data = await fileToBase64(file);

        // Get CSRF token
        const csrfToken = form.querySelector('input[name="_token"]')?.value || 
                         document.querySelector('meta[name="csrf-token"]')?.content;

        if (!csrfToken) {
            throw new Error('CSRF token not found. Please refresh the page.');
        }

        // Prepare base64 upload payload
        const payload = {
            file_data: base64Data,
            file_name: file.name,
            file_size: file.size,
            file_type: file.type,
            client_name: form.querySelector('input[name="client_name"]')?.value || null
        };

        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });

        const data = await readDocumentResponse(response, 'The document could not be uploaded.');
        
        if (!data.success) throw new Error(data.message || 'The document could not be uploaded.');
        
        form.reset();
        showDocumentMessage(data.message);
        return refreshDocumentsTable();

    } catch (error) {
        showDocumentMessage(error.message, 'error');
    } finally {
        form.dataset.uploading = 'false';
        if (heading && originalHeading) heading.textContent = originalHeading;
    }
}

function copyShareLink(url) {
    navigator.clipboard?.writeText(url);
    alert('Share link copied to clipboard: ' + url);
}

$(document).ready(function () {
    initializeDocumentsTable();

    $(document).on('submit', '#documentUploadForm, #deleteDocForm', function (event) {
        event.preventDefault();

        if (this.id === 'documentUploadForm') {
            handleDocumentUpload(this);
            return;
        }

        fetch(this.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json'
            },
            body: new FormData(this)
        })
            .then(response => readDocumentResponse(response, 'The document could not be deleted.'))
            .then(data => {
                if (!data.success) throw new Error(data.message || 'The document could not be deleted.');
                bootstrap.Modal.getInstance(document.getElementById('deleteDocModal'))?.hide();
                showDocumentMessage(data.message);
                return refreshDocumentsTable();
            })
            .catch(error => showDocumentMessage(error.message, 'error'));
    });

    const dropZone = document.getElementById('dropZoneContainer');
    if (!dropZone) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, event => {
            event.preventDefault();
            event.stopPropagation();
        });
    });

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.background = 'rgba(0, 168, 181, 0.1)';
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.style.background = 'rgba(0, 168, 181, 0.03)';
        });
    });

    dropZone.addEventListener('drop', event => {
        const fileInput = document.getElementById('documentFileInput');
        fileInput.files = event.dataTransfer.files;
        handleDocumentUpload(fileInput.form);
    });
});

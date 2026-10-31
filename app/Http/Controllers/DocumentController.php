<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private const DOCUMENT_UPLOAD_RULES = [
        'required',
        'file',
        'max:10240',
        'mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
        'extensions:pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
    ];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'uploaded_from' => ['nullable', 'date'],
            'uploaded_to' => ['nullable', 'date', 'after_or_equal:uploaded_from'],
        ]);
        $query = Document::query();
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(fn ($documents) => $documents->where('name', 'like', "%{$search}%")
                ->orWhere('client_name', 'like', "%{$search}%"));
        }
        if (!empty($filters['uploaded_from'])) $query->whereDate('created_at', '>=', $filters['uploaded_from']);
        if (!empty($filters['uploaded_to'])) $query->whereDate('created_at', '<=', $filters['uploaded_to']);

        $documents = $query->latest()->paginate(10)->withQueryString();

        return view('pages.utilities.documents', compact('documents'));
    }

    public function store(Request $request)
    {
        try {
            // Check if this is a base64 encoded upload (WAF bypass)
            if ($request->has('file_data') && $request->has('file_name')) {
                return $this->storeBase64Upload($request);
            }

            // Traditional multipart upload
            $request->validate(['document' => self::DOCUMENT_UPLOAD_RULES]);

            $file = $request->file('document');
            
            if (!$file || !$file->isValid()) {
                throw new \Exception('Invalid or missing file upload.');
            }

            $path = $file->store('documents', 'local');
            if ($path === false) {
                throw new \RuntimeException('Unable to store the uploaded document.');
            }

            $document = Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
                'client_name' => $request->input('client_name') ?? null,
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Document uploaded successfully.',
                    'document' => $document,
                ]);
            }

            return back()->with('success', 'Document uploaded successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all()),
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Document upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document upload failed: ' . $e->getMessage(),
                ], 500);
            }

            return back()->with('error', 'Document upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Test endpoint for debugging base64 uploads
     */
    public function testUpload(Request $request)
    {
        try {
            \Log::info('Test upload endpoint hit', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'has_file_data' => $request->has('file_data'),
                'has_file_name' => $request->has('file_name'),
                'input_keys' => array_keys($request->all()),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Test endpoint working',
                'received' => [
                    'method' => $request->method(),
                    'content_type' => $request->header('Content-Type'),
                    'has_file_data' => $request->has('file_data'),
                    'has_file_name' => $request->has('file_name'),
                    'file_name' => $request->input('file_name'),
                    'file_size' => $request->input('file_size'),
                    'data_length' => strlen($request->input('file_data', '')),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    /**
     * Handle base64 encoded file upload (WAF bypass workaround)
     */
    protected function storeBase64Upload(Request $request)
    {
        try {
            // Validate base64 upload
            $request->validate([
                'file_data' => 'required|string|max:14000000',
                'file_name' => 'required|string|max:255',
                'file_size' => 'required|integer|min:1|max:10485760',
                'file_type' => 'nullable|string',
            ]);

            $fileName = $request->input('file_name');
            $fileData = $request->input('file_data');
            $fileSize = $request->input('file_size');
            
            // Remove data:mime/type;base64, prefix if present
            if (strpos($fileData, 'base64,') !== false) {
                $fileData = substr($fileData, strpos($fileData, 'base64,') + 7);
            }

            // Decode base64
            $decodedData = base64_decode($fileData, true);
            
            if ($decodedData === false) {
                throw new \Exception('Invalid base64 data.');
            }

            // Verify decoded size matches
            if (strlen($decodedData) !== $fileSize) {
                throw new \Exception('File size mismatch. Upload may be corrupted.');
            }

            $temporaryPath = tempnam(sys_get_temp_dir(), 'document-upload-');
            if ($temporaryPath === false) {
                throw new \RuntimeException('Unable to validate the uploaded document.');
            }

            try {
                if (file_put_contents($temporaryPath, $decodedData) === false) {
                    throw new \RuntimeException('Unable to validate the uploaded document.');
                }

                $uploadedFile = new UploadedFile(
                    $temporaryPath,
                    $fileName,
                    mime_content_type($temporaryPath) ?: null,
                    UPLOAD_ERR_OK,
                    true
                );
                Validator::make(
                    ['document' => $uploadedFile],
                    ['document' => self::DOCUMENT_UPLOAD_RULES]
                )->validate();
            } finally {
                if (is_file($temporaryPath)) {
                    unlink($temporaryPath);
                }
            }

            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $storageName = Str::uuid() . '.' . $extension;
            $storagePath = 'documents/' . $storageName;

            if (!Storage::disk('local')->put($storagePath, $decodedData)) {
                throw new \RuntimeException('Unable to store the uploaded document.');
            }

            // Create database record
            $document = Document::create([
                'name' => $fileName,
                'path' => $storagePath,
                'size' => $fileSize,
                'client_name' => $request->input('client_name') ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'document' => $document,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(' ', $e->validator->errors()->all()),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Base64 document upload failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Document upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download(Document $document)
    {
        if (Storage::disk('local')->exists($document->path)) {
            return $this->downloadDocument($document);
        }

        return back()->with('error', 'File not found.');
    }

    public function share($token)
    {
        $document = Document::where('token', $token)->firstOrFail();
        if (Storage::disk('local')->exists($document->path)) {
            return $this->downloadDocument($document);
        }
        abort(404);
    }

    private function downloadDocument(Document $document)
    {
        return Storage::disk('local')->download($document->path, $document->name, [
            'Content-Type' => 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, Document $document)
    {
        if (Storage::disk('local')->exists($document->path)) {
            Storage::disk('local')->delete($document->path);
        }
        $document->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Document deleted successfully.']);
        }

        return back()->with('success', 'Document deleted successfully.');
    }
}

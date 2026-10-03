<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Models\EnrollmentApplicationDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class EnrollmentApplicationDocumentController extends Controller
{
    public function show(
        EnrollmentApplication $application,
        EnrollmentApplicationDocument $document,
    ): Response {
        abort_unless($document->enrollment_application_id === $application->id, 404);

        return Storage::disk('local')->download(
            $document->file_path,
            $document->original_name,
            [
                'Content-Type' => $document->mime_type,
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Student;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Services\PDFLayoutService;
use Spatie\LaravelPdf\Facades\Pdf;
use App\Services\PdfScalingService;
use App\Services\ReportCardService;
use Spatie\Browsershot\Browsershot;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Container\Attributes\Log;

class ReportCardController extends Controller
{
    protected $reportCardService;

    public function __construct(ReportCardService $reportCardService)
    {
        $this->reportCardService = $reportCardService;
    }

    public function preview(Student $student, Request $request)
    {

        try {
            // Get current tenant
            $school = $student->school;

            // Get current term and session from request or school's current settings
            $termId = $request->query('term_id') ??
                $school->terms()
                ->where('is_current', true)
                ->first()?->id;

            $sessionId = $request->query('session_id') ??
                $school->academicSessions()
                ->where('is_current', true)
                ->first()?->id;

            $templateId = $request->query('template_id');

            if (!$termId || !$sessionId) {
                throw new \Exception('No active term or session found');
            }
            // dd( $templateId);
            // Generate report data
            $reportData = $this->reportCardService->generateReport(
                $student,
                $termId,
                $sessionId,
                $templateId
            );

            $signatures = [
                'class_teacher' => null,
                'principal' => null
            ];

            foreach (['class_teacher', 'principal'] as $type) {
                if (!empty($reportData['comments'][$type]['digital_signature']['signature_url'])) {
                    // Extract the path after '/storage/'
                    $url = $reportData['comments'][$type]['digital_signature']['signature_url'];
                    $parsedUrl = parse_url($url);
                    $pathParts = explode('/storage/', $parsedUrl['path']);
                    $signaturePath = end($pathParts);

                    $fullPath = storage_path('app/public/' . $signaturePath);

                    if (File::exists($fullPath)) {
                        $signatureData = base64_encode(File::get($fullPath));

                        $mimeType = File::mimeType($fullPath);
                        $signatures[$type] = "data:{$mimeType};base64,{$signatureData}";
                        // dd($signatures[$type]);
                    }
                }
            }
            $schoolLogo = asset('storage/' . $school->logo);
            //  dd($reportData);
            return view('pdfs.term-report-card-pdf', [
                'report' => $reportData,
                'school' => $school,
                'student' => $student,
                'signatures' => $signatures,
                'isPreview' => true,
                'schoolLogo' => $schoolLogo,

            ]);
        } catch (\Exception $e) {
            // Log error
            // Log::info('Report Preview Error', [
            //     'error' => $e->getMessage(),
            //     'student' => $student->id,
            //     'school' => $school->slug ?? null,
            // ]);

            // Return error view or redirect
            return back()->withError('Unable to generate report preview: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use App\Models\Template;
use App\Models\Admission;
use App\Models\AdmLtrTemplate;
use Filament\Facades\Filament;
use App\Models\VariableTemplate;
use Livewire\Volt\Compilers\Mount;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use App\Services\TemplateRenderService;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Sms\Resources\AdmissionResource;
use App\Filament\Sms\Resources\TemplateResource\Pages\ListTemplates;

class ViewAdmissionLetter extends ViewRecord
{
    protected static string $resource = AdmissionResource::class;

    protected static string $view = 'filament.sms.resources.admission-resource.pages.view-admission-letter';

    public $content;
    public $admissionLetter;
    public $school;
    public $admission;
    public $logoUrl;
    public $logoData;

    public ?Template $template = null;
    public ?string $renderedContent = null;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->school = Filament::getTenant();
        $this->authorizeAccess();

        $this->template = Template::where('school_id', Filament::getTenant()->id)
            ->where('category', 'admission_letter')
            ->where('is_active', true)
            ->first();

        if ($this->template) {
            $renderer = new TemplateRenderService($this->template);
            $this->renderedContent = $renderer->renderForAdmission($this->record);
        }
        // Fix logo checking and loading
        $this->logoData = null;
        if ($this->school->logo) {
            $logoPath = str_replace('public/', '', $this->school->logo);

            if (Storage::disk('public')->exists($logoPath)) {
                $fullLogoPath = Storage::disk('public')->path($logoPath);
                $extension = pathinfo($fullLogoPath, PATHINFO_EXTENSION);
                $this->logoData = 'data:image/' . $extension . ';base64,' . base64_encode(
                    Storage::disk('public')->get($logoPath)
                );
            }
        }
    }

    public function downloadPdf()
    {
        try {
           
            $fileName = sprintf(
                '%s-admission-letter-%s.pdf',
                $this->school->slug,
                $this->record->full_name
            );
            $directory = storage_path("app/public/{$this->school->slug}/documents");

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $admissionLetterPath = "{$directory}/{$fileName}";


            $renderer = new TemplateRenderService($this->template);
            $content = $renderer->renderForAdmission($this->record);

            // Generate PDF with logo data
            Pdf::view('pdfs.admission-letter', [
                'content' => $content,
                'school' => $this->school,
                'admission' => $this->record,
                'logoData' => $this->logoData
            ])
                ->format('a4')
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $browsershot->setChromePath(config('app.chrome_path'))
                        ->format('A4')
                        ->emulateMedia('print') // Important for print styles
                        ->screenResolution(1280, 1024) // Better resolution
                        ->scale(1) // Default scale
                        ->margins(0, 0, 0, 0) // Let CSS handle margins
                        ->showBackground()
                        ->waitUntilNetworkIdle();
                })
                ->save($admissionLetterPath);

            // Check if file was created successfully
            if (!file_exists($admissionLetterPath)) {
                throw new \Exception('Failed to generate PDF file');
            }

            Notification::make()
                ->title('Admission Letter downloaded successfully.')
                ->success()
                ->send();

            return response()->download($admissionLetterPath, $fileName, [
                'Content-Type' => 'application/pdf',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Log the error
            Log::error('PDF Generation Failed', [
                'error' => $e->getMessage(),
                'admission_id' => $this->record->id,
                'school' => $school->slug ?? null
            ]);

            Notification::make()
                ->title('Error generating admission letter')
                ->body('Something went wrong while generating the admission letter.')
                ->danger()
                ->send();

            return null;
        }
    }
}

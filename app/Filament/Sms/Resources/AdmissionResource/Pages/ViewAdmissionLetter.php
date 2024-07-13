<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Carbon\Carbon;
use Filament\Actions;
use App\Models\Template;
use App\Models\Admission;
use App\Models\AdmLtrTemplate;
use App\Models\VariableTemplate;
use Livewire\Volt\Compilers\Mount;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Sms\Resources\AdmissionResource;
use App\Filament\Sms\Resources\TemplateResource\Pages\ListTemplates;

class ViewAdmissionLetter extends ViewRecord
{
    protected static string $resource = AdmissionResource::class;

    protected static string $view = 'filament.sms.pages.view-admission-letter';

    public $content;
    public $admissionLetter;
    public $school;
    public $admission;
    public $logoUrl;

    public function mount($record): void
    {

        try {
            $this->admission = Admission::findOrFail($record);
        } catch (\Exception $e) {
            // Handle the exception, e.g., log it or set an error message
            return;
        }
        // dd($record);
        $user = auth()->user();
        $school = $user->schools->first();
        $this->school = $school;
        $this->record = Template::where('school_id', $school->id)->first();
        // dd($this->record);
        if ($this->record == null) {
            Notification::make()
                ->title('No Template Found')
                ->info()
                ->send();
            // $this->redirect(ListTemplates::class);
            $this->redirecTo($school);
        }
        $this->admissionLetter = $this->record->content ?? '';

        $this->logoUrl = asset('storage/' . $this->school->logo);

        $variables = VariableTemplate::where('school_id', $school->id)->get();
        $replacements = [];

        foreach ($variables as $variable) {
            $columnValue = $this->admission->{$variable->mapping} ?? $variable->default_value;

            if ($variable->mapping == 'admission_date' && $columnValue) {
                $columnValue = Carbon::parse($columnValue)->format('F j, Y');
            }

            $replacements[$variable->variable_name] = $columnValue;
        }

        $this->content = strtr($this->admissionLetter, $replacements);
    }

    public function redirecTo($school)
    {

        return $this->redirectRoute('filament.sms.resources.templates.index', ['tenant' => $school->slug]);
    }

    public function downloadAdmissionLetter()
    {
        $pdfName = $this->school->name . '_' . now() . '_admission.pdf';
        $receiptPath = storage_path("app/{$pdfName}");
        
       
        $logoUrl = asset('storage/' . $this->school->logo);
        // Pdf::view('pdfs.admission-letter', [
        //     'content' => $this->content,
        //     'school' => $this->school,
        //     'logoUrl' => $logoUrl, // Pass the logo URL to the view
        // ])->withBrowsershot(function (Browsershot $browsershot) {
        //     $browsershot->setChromePath(config('app.chrome_path'));
        // })->save($receiptPath);
        $html = view('pdfs.admission-letter', [
            'content' => $this->content,
            'school' => $this->school,
            'logoUrl' => $logoUrl, // Pass the logo URL to the view
        ])->render();
        Browsershot::html($html)
        ->noSandbox()
        ->setChromePath(config('app.chrome_path'))
        ->showBackground()
        ->format('A4')
        ->save($receiptPath);

        Notification::make()
            ->title('Downloaded successfully.')
            ->success()
            ->send();

        return response()->download($receiptPath, $pdfName, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Template;
use App\Models\Admission;
use Illuminate\Http\Request;
use App\Models\VariableTemplate;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use Filament\Notifications\Notification;



class AdmissionLetterController extends Controller
{
    public $school;
    public $record;
    public $content;
    public $admissionLetter;
    public $logoUrl;

    public function show($admission)
    {
        try {
            $admission = Admission::findOrFail($admission);
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
            return;
        }
        $this->admissionLetter = $this->record->content ?? '';

        $this->logoUrl = asset('storage/' . $this->school->logo);

        $variables = VariableTemplate::where('school_id', $school->id)->get();
        $replacements = [];

        foreach ($variables as $variable) {
            $columnValue = $admission->{$variable->mapping} ?? $variable->default_value;

            if ($variable->mapping == 'admission_date' && $columnValue) {
                $columnValue = Carbon::parse($columnValue)->format('F j, Y');
            }

            $replacements[$variable->variable_name] = $columnValue;
        }

        $this->content = strtr($this->admissionLetter, $replacements);

        return view('pdfs.admission-letter', ['content' => $this->content, 'school' => $this->school, 'logoUrl' => $this->logoUrl]);
    }

    public function downloadAdmissionLetter($admission)
    {
        try {
            $admission = Admission::findOrFail($admission);
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
            return;
        }
        $this->admissionLetter = $this->record->content ?? '';

        $this->logoUrl = asset('storage/' . $this->school->logo);

        $variables = VariableTemplate::where('school_id', $school->id)->get();
        $replacements = [];

        foreach ($variables as $variable) {
            $columnValue = $admission->{$variable->mapping} ?? $variable->default_value;

            if ($variable->mapping == 'admission_date' && $columnValue) {
                $columnValue = Carbon::parse($columnValue)->format('F j, Y');
            }

            $replacements[$variable->variable_name] = $columnValue;
        }

        $this->content = strtr($this->admissionLetter, $replacements);
        
        $pdfName = $this->school->name . '_' . now() . '_admission.pdf';
        $receiptPath = storage_path("app/{$pdfName}");
        
        $logoUrl = asset('storage/' . $this->school->logo);
        Pdf::view('pdfs.admission-letter', [
            'content' => $this->content,
            'school' => $this->school,
            'logoUrl' => $logoUrl, // Pass the logo URL to the view
        ])->withBrowsershot(function (Browsershot $browsershot) {
            $browsershot->setChromePath(config('app.chrome_path'));
        })->save($receiptPath);

        Notification::make()
            ->title('Downloaded successfully.')
            ->success()
            ->send();

        return response()->download($receiptPath, $pdfName, [
            'Content-Type' => 'application/pdf',
        ])->deleteFileAfterSend(true);
    }
}

<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use Filament\Actions;
use App\Models\Status;
use App\Models\Student;
use App\Models\Admission;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use App\Services\AdmissionNumberGenerator;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Sms\Resources\StudentResource;
use App\Filament\Sms\Resources\AdmissionResource;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class CreateAdmission extends CreateRecord
{
    protected static string $resource = AdmissionResource::class;

    public function mount(): void
    {
        parent::mount();

        // Generate a preview admission number
        $generator = new AdmissionNumberGenerator();
        $admissionNumber = $generator->generate();

        // Pre-fill the form with generated number and default dates
        $this->form->fill([
            'admission_number' => $admissionNumber,
            'application_date' => now() ?? null,
            'admitted_date' => now() ?? null,
            'academic_session_id' => config('app.current_session')->id ?? null,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $session = AcademicSession::find($data['academic_session_id']);
        $status = Status::find($data['status_id']);
        $sessionName = $session->name ?? null;

        // Remove admission number if status is not approved
        if ($status?->name !== 'approved') {
            unset($data['admission_number']);
        }

        // Create Admission record
        $admissionData =  [

            'academic_session_id' => $data['academic_session_id'],
            'session' => $sessionName,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'],
            'date_of_birth' => $data['date_of_birth'],
            'gender' => $data['gender'],
            'address' => $data['address'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'state_id' => $data['state_id'],
            'lga_id' => $data['lga_id'],
            'religion' => $data['religion'],
            'blood_group' => $data['blood_group'],
            'genotype' => $data['genotype'],
            'previous_school_name' => $data['previous_school_name'],
            'previous_class' => $data['previous_class'],
            'admitted_date' => $status?->name === 'approved' ? $data['admitted_date'] : null,
            'application_date' => $data['application_date'],
            'admission_number' => $data['admission_number'] ?? null,
            'class_room_id' => $data['class_room_id'] ?? null,
            'status_id' => $data['status_id'],
            'guardian_name' => $data['guardian_name'],
            'guardian_relationship' => $data['guardian_relationship'],
            'guardian_phone_number' => $data['guardian_phone_number'],
            'guardian_email' => $data['guardian_email'],
            'guardian_address' => $data['guardian_address'],
            'emergency_contact_name' => $data['emergency_contact_name'],
            'emergency_contact_relationship' => $data['emergency_contact_relationship'],
            'emergency_contact_phone_number' => $data['emergency_contact_phone_number'],
            'emergency_contact_email' => $data['emergency_contact_email'],
            'disability_type' => $data['disability_type'] ?? null,
            'disability_description' => $data['disability_description'] ?? null,
            'passport_photograph' => $data['passport_photograph'] ?? null,

        ];

        $record = new ($this->getModel())($admissionData);

        if (
            static::getResource()::isScopedToTenant() &&
            ($tenant = Filament::getTenant())
        ) {
            return $this->associateRecordWithTenant($record, $tenant);
        }

        $record->save();

        return $record;
    }

    protected function afterCreate(): void
    {
        if ($this->record->status?->name === 'approved') {
            DB::transaction(function () {
                // Create student record if admission is approved and has admission number
                if (
                    $this->record->status?->name === 'approved'
                    && $this->record->admission_number
                    && !$this->record->student()->exists()
                ) {
                    $student = Student::create([
                        'school_id' => Filament::getTenant()->id,
                        'admission_id' => $this->record->id,
                        'class_room_id' => $this->record->class_room_id,
                        'status_id' => Status::where('type', 'student')
                            ->where('name', 'active')
                            ->first()?->id,
                        'first_name' => $this->record->first_name,
                        'last_name' => $this->record->last_name,
                        'middle_name' => $this->record->middle_name,
                        'date_of_birth' => $this->record->date_of_birth,
                        'phone_number' => $this->record->phone_number,
                        'profile_picture' => $this->record->passport_photograph,
                        'admission_number' => $this->record->admission_number,
                        'created_by' => Auth::id(),
                    ]);

                    // Update admission status to enrolled
                    $enrolledStatus = Status::where('type', 'admission')
                        ->where('name', 'enrolled')
                        ->first();

                    if ($enrolledStatus) {
                        $this->record->update(['status_id' => $enrolledStatus->id]);
                    }

                    // Send admission notification
                    $notificationEmails = collect([$this->record->guardian_email, $this->record->email])
                        ->filter()
                        ->unique()
                        ->values();

                    foreach ($notificationEmails as $email) {
                        NotificationFacade::route('mail', $email)
                            ->notify(new \App\Notifications\AdmissionApprovedNotification($this->record));
                    }

                    Notification::make()
                        ->success()
                        ->title('Student Record Created')
                        ->body("Student {$student->full_name} has been automatically enrolled")
                        ->persistent()
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view')
                                ->button()
                                ->url(fn() => StudentResource::getUrl('view', [
                                    'tenant' => Filament::getTenant()->slug,
                                    'record' => $student->id,
                                ]))
                        ])
                        ->send();
                }

                // Generate PDF
                $pdfPath = "admissions/{$this->record->school->slug}/{$this->record->admission_number}.pdf";

                Pdf::view('pdfs.admission-letter', [
                    'content' => $this->record->template?->content,
                    'school' => $this->record->school,
                    'admission' => $this->record,
                    'logoData' => $this->getSchoolLogoData()
                ])
                    ->format('a4')
                    ->save(Storage::path($pdfPath));

                // Send notifications with PDF
                $notificationEmails = collect([$this->record->guardian_email, $this->record->email])
                    ->filter()
                    ->unique()
                    ->values();

                foreach ($notificationEmails as $email) {
                    NotificationFacade::route('mail', $email)
                        ->notify(new \App\Notifications\AdmissionApprovedNotification($this->record, $pdfPath));
                }
            });
        }
    }

    protected function getSchoolLogoData(): ?string
    {
        if (!$this->record->school->logo) {
            return null;
        }

        $logoPath = str_replace('public/', '', $this->record->school->logo);
        if (!Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $fullLogoPath = Storage::disk('public')->path($logoPath);
        $extension = pathinfo($fullLogoPath, PATHINFO_EXTENSION);

        return 'data:image/' . $extension . ';base64,' . base64_encode(
            Storage::disk('public')->get($logoPath)
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Format dates for form fields
        $data['application_date'] = $data['application_date'] ? date('Y-m-d', strtotime($data['application_date'])) : now()->format('Y-m-d');
        $data['admitted_date'] = $data['admitted_date'] ? date('Y-m-d', strtotime($data['admitted_date'])) : null;

        return $data;
    }
}

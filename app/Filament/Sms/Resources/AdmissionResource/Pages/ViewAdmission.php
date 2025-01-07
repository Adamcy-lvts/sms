<?php

// ViewAdmission.php 
namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use App\Models\Status;
use App\Models\Student;
use App\Models\Template;
use App\Models\Admission;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Infolists\Infolist;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\ApplicationApprovedMail;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Illuminate\Mail\Mailables\Address;
use App\Services\TemplateRenderService;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Sms\Resources\StudentResource;
use App\Filament\Sms\Resources\AdmissionResource;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class ViewAdmission extends ViewRecord
{
    protected static string $resource = AdmissionResource::class;

    // Define the infolist to display admission details
    public function infolist(Infolist $infolist): Infolist
    {

        return $infolist
            ->schema([
                // Header Section
                \Filament\Infolists\Components\Grid::make(4)
                    ->schema([
                        ImageEntry::make('passport_photograph')
                            ->circular()
                            ->size(100),

                        \Filament\Infolists\Components\Group::make([
                            TextEntry::make('admission_number')
                                ->label('Application ID')
                                ->icon('heroicon-m-identification')
                                ->copyable()
                                ->weight('bold'),
                            TextEntry::make('application_date')
                                ->label('Applied On')
                                ->dateTime('d M Y')
                                ->icon('heroicon-m-calendar'),
                        ]),

                        \Filament\Infolists\Components\Group::make([
                            TextEntry::make('academicSession.name')
                                ->label('Session')
                                ->icon('heroicon-m-academic-cap'),
                            TextEntry::make('status.name')
                                ->badge()
                                ->label('Status')
                                ->color(fn(string $state): string => match ($state) {
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    default => 'gray',
                                }),
                        ])->columnSpan(2),
                    ])->columnSpanFull(),

                // Main Content Grid
                \Filament\Infolists\Components\Grid::make(2)
                    ->schema([
                        // Left Column
                        \Filament\Infolists\Components\Group::make([
                            // Personal Information
                            \Filament\Infolists\Components\Section::make('Personal Information')
                                ->icon('heroicon-o-user')
                                ->collapsible()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(2)->schema([
                                        TextEntry::make('first_name'),
                                        TextEntry::make('last_name'),
                                        TextEntry::make('middle_name'),
                                        TextEntry::make('gender')->badge(),
                                        TextEntry::make('date_of_birth')
                                            ->date('d M Y'),
                                        TextEntry::make('age')
                                            ->state(function (Admission $record): string {
                                                if (!$record?->date_of_birth) return 'N/A';

                                                $birthDate = \Carbon\Carbon::parse($record->date_of_birth);
                                                $age = max(0, $birthDate->age);

                                                return $age . ' years';
                                            }),
                                    ]),
                                    TextEntry::make('address')->columnSpanFull(),
                                    \Filament\Infolists\Components\Grid::make(3)->schema([
                                        TextEntry::make('state.name')->label('State'),
                                        TextEntry::make('lga.name')->label('LGA'),
                                        TextEntry::make('nationality'),
                                    ]),
                                ]),

                            // Medical Information
                            \Filament\Infolists\Components\Section::make('Medical Information')
                                ->icon('heroicon-o-heart')
                                ->collapsible()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(3)->schema([
                                        TextEntry::make('blood_group'),
                                        TextEntry::make('genotype'),
                                        TextEntry::make('disability_type')
                                            ->placeholder('None'),
                                    ]),
                                    TextEntry::make('disability_description')
                                        ->visible(fn($record) => filled($record->disability_type))
                                        ->columnSpanFull(),
                                ]),

                            // Emergency Contact
                            \Filament\Infolists\Components\Section::make('Emergency Contact')
                                ->icon('heroicon-o-exclamation-circle')
                                ->collapsible()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(2)->schema([
                                        TextEntry::make('emergency_contact_name'),
                                        TextEntry::make('emergency_contact_relationship'),
                                        TextEntry::make('emergency_contact_phone_number')
                                            ->icon('heroicon-m-phone'),
                                        TextEntry::make('emergency_contact_email')
                                            ->icon('heroicon-m-envelope'),
                                    ]),
                                ]),
                        ])->columnSpan(1),

                        // Right Column
                        \Filament\Infolists\Components\Group::make([
                            // Contact Information
                            \Filament\Infolists\Components\Section::make('Contact Information')
                                ->icon('heroicon-o-phone')
                                ->collapsible()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(2)->schema([
                                        TextEntry::make('phone_number')
                                            ->icon('heroicon-m-phone'),
                                        TextEntry::make('email')
                                            ->icon('heroicon-m-envelope'),
                                    ]),
                                ]),

                            // Parent/Guardian Information
                            \Filament\Infolists\Components\Section::make('Parent/Guardian Information')
                                ->icon('heroicon-o-user-group')
                                ->collapsible()
                                ->schema([
                                    \Filament\Infolists\Components\Grid::make(2)->schema([
                                        TextEntry::make('guardian_name'),
                                        TextEntry::make('guardian_relationship'),
                                        TextEntry::make('guardian_phone_number')
                                            ->icon('heroicon-m-phone'),
                                        TextEntry::make('guardian_email')
                                            ->icon('heroicon-m-envelope'),
                                    ]),
                                    TextEntry::make('guardian_address')
                                        ->columnSpanFull(),
                                ]),

                            // Previous School
                            \Filament\Infolists\Components\Section::make('Previous Education')
                                ->icon('heroicon-o-academic-cap')
                                ->collapsible()
                                ->schema([
                                    TextEntry::make('previous_school_name'),
                                    TextEntry::make('previous_class'),
                                ]),

                            // Review Notes
                            \Filament\Infolists\Components\Section::make('Review Notes')
                                ->icon('heroicon-o-clipboard-document-list')
                                ->collapsible()
                                ->hidden(fn($record) => !filled($record->review_notes))
                                ->schema([
                                    TextEntry::make('review_notes')
                                        ->markdown()
                                        ->columnSpanFull(),
                                ]),
                        ])->columnSpan(1),
                    ]),
            ])
            ->columns(12);
    }

    // Define header actions
    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                // Approve action
                Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([

                        Section::make('Approval Details')
                            ->schema([
                                TextInput::make('admission_number')
                                    ->label('Admission Number')
                                    ->required()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(fn(Admission $record) => $record->admission_number ? $record->admission_number : (new \App\Services\AdmissionNumberGenerator())->generate())
                                    ->helperText('A new admission number has been generated. You can modify it if needed.')
                                    ->unique(Admission::class, 'admission_number', ignorable: fn($record) => $record),
                                Toggle::make('send_notification')
                                    ->label('Send Acceptance Letter')
                                    ->default(true),
                                Toggle::make('enroll_now')
                                    ->label('Enroll Student Now')
                                    ->live()
                                    ->helperText('Creates student record immediately'),
                                Select::make('class_room_id')
                                    ->label('Assign Class')
                                    ->options(ClassRoom::pluck('name', 'id'))
                                    ->visible(fn($get) => $get('enroll_now')),
                            ])
                    ])
                    ->action(function (array $data) {
                        DB::beginTransaction();
                        try {
                            $school = Filament::getTenant();
                            // Update admission status
                            $this->record->update([
                                'status_id' => Status::where('type', 'admission')->where('name', 'approved')->first()->id,
                                'admitted_date' => now(),
                                'admission_number' => $data['admission_number']
                            ]);

                            // In your action:
                            if ($data['send_notification']) {
                                try {
                                    // 1. Get or create template and render content
                                    $this->template = Template::where('school_id', $school->id)
                                        ->where('category', 'admission_letter')
                                        ->where('is_active', true)
                                        ->first();

                                    $renderer = new TemplateRenderService($this->template);
                                    $content = $renderer->renderForAdmission($this->record);

                                    // 2. Use existing directory and ensure it exists
                                    $directory = "public/{$school->slug}/documents";
                                    if (!Storage::exists($directory)) {
                                        Storage::makeDirectory($directory, 0755, true);
                                    }

                                    // 3. Generate PDF name and path
                                    $pdfName = Str::slug("admission-letter-{$this->record->admission_number}") . '.pdf';
                                    $storagePath = "{$directory}/{$pdfName}";
                                    $fullPath = Storage::path($storagePath);

                                    // 4. Generate PDF
                                    Pdf::view('pdfs.admission-letter', [
                                        'content' => $content,
                                        'school' => $this->record->school,
                                        'admission' => $this->record,
                                        'logoData' => $this->getSchoolLogoData()
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
                                        ->save($fullPath);

                                    // 5. Collect email recipients
                                    $notificationEmails = collect([
                                        $this->record->guardian_email => $this->record->guardian_name,
                                        $this->record->email => $this->record->full_name
                                    ])
                                        ->filter(fn($name, $email) => filter_var($email, FILTER_VALIDATE_EMAIL))
                                        ->map(fn($name, $email) => new Address($email, $name));

                                    // 6. Send emails if we have recipients
                                    if ($notificationEmails->isNotEmpty()) {
                                        Mail::to($notificationEmails)
                                            ->queue(new ApplicationApprovedMail(
                                                admission: $this->record,
                                                pdfPath: $storagePath,
                                                pdfName: $pdfName
                                            ));

                                        // Log successful email queuing
                                        Log::info('Admission approval emails queued', [
                                            'admission_id' => $this->record->id,
                                            'recipients' => $notificationEmails->keys()->toArray()
                                        ]);
                                    } else {
                                        // Log warning if no valid email recipients
                                        Log::warning('No valid email recipients for admission approval', [
                                            'admission_id' => $this->record->id,
                                            'guardian_email' => $this->record->guardian_email,
                                            'student_email' => $this->record->email
                                        ]);
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to process admission approval notification', [
                                        'error' => $e->getMessage(),
                                        'admission_id' => $this->record->id,
                                        'trace' => $e->getTraceAsString()
                                    ]);

                                    throw new \Exception('Failed to send admission approval notification: ' . $e->getMessage());
                                }
                            }

                            // Create student record if enrolling now
                            if ($data['enroll_now']) {
                                // Create student record
                                $student =  Student::create([
                                    'school_id' => Filament::getTenant()->id,
                                    'admission_id' => $this->record->id,
                                    'class_room_id' => $data['class_room_id'],
                                    'status_id' => Status::where('type', 'student')->where('name', 'active')->first()->id,
                                    'first_name' => $this->record->first_name,
                                    'last_name' => $this->record->last_name,
                                    'middle_name' => $this->record->middle_name,
                                    'date_of_birth' => $this->record->date_of_birth,
                                    'phone_number' => $this->record->phone_number,
                                    'profile_picture' => $this->record->passport_photograph,
                                    'admission_number' => $this->record->admission_number,
                                    'created_by' => Auth::id(),
                                ]);
                            }

                            DB::commit();
                            Notification::make()
                                ->success()
                                ->title('Admission Approved & Student Enrolled')
                                ->body("Successfully processed admission for {$this->record->full_name}")
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
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->danger()
                                ->title('Error Processing Admission')
                                ->body('There was an error processing the admission. Please try again.')
                                ->send();

                            throw $e;
                        }
                    })
                    ->visible(fn() => $this->record->status->name === 'pending'),

                // Reject action
                Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Textarea::make('rejection_reason')
                            ->required()
                            ->label('Reason for Rejection')
                    ])
                    ->action(function (array $data) {
                        $this->record->update([
                            'status_id' => Status::where('type', 'admission')->where('name', 'rejected')->first()->id,
                            'notes' => $data['rejection_reason']
                        ]);

                        // // Send rejection notification
                        // Mail::to($this->record->guardian_email)
                        //     ->send(new AdmissionRejectionMail($this->record));

                        Notification::make()
                            ->warning()
                            ->title('Admission Rejected')
                            ->send();
                    })
                    ->visible(fn() => $this->record->status->name === 'pending'),

                // Enroll action for approved admissions
                Action::make('enroll')
                    ->icon('heroicon-o-academic-cap')
                    ->requiresConfirmation()
                    ->form([
                        Select::make('class_room_id')
                            ->label('Class')
                            ->options(ClassRoom::pluck('name', 'id'))
                            ->required(),
                        Toggle::make('create_user')
                            ->label('Create Student Account')
                            ->default(true)
                    ])
                    ->action(function (array $data) {
                        $student =  Student::create([
                            'school_id' => Filament::getTenant()->id,
                            'admission_id' => $this->record->id,
                            'class_room_id' => $data['class_room_id'],
                            'status_id' => Status::where('type', 'student')->where('name', 'active')->first()->id,
                            'first_name' => $this->record->first_name,
                            'last_name' => $this->record->last_name,
                            'middle_name' => $this->record->middle_name,
                            'date_of_birth' => $this->record->date_of_birth,
                            'phone_number' => $this->record->phone_number,
                            'profile_picture' => $this->record->passport_photograph,
                            'admission_number' => $this->record->admission_number,
                            'created_by' => Auth::id(),
                        ]);
                    })
                    ->visible(fn() => $this->record->status->name === 'approved' && !$this->record->student()->exists()),

                // View/Download Letter
                Action::make('view_letter')
                    ->icon('heroicon-o-document-text')
                    ->url(fn() => route('filament.sms.resources.admissions.view-letter', [
                        'tenant' => Filament::getTenant()->slug,
                        'record' => $this->record,
                    ]))
                    ->openUrlInNewTab()
                    ->visible(fn() => $this->record->status->name === 'approved'),
            ])
        ];
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
}

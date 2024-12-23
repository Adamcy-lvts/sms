<?php

namespace App\Filament\Imports;

use App\Models\Lga;
use App\Models\State;
use App\Models\Status;
use App\Models\Student;
use App\Models\Admission;
use App\Models\ClassRoom;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Imports\Importer;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

class StudentAdmissionImporter extends Importer
{
    protected static ?string $model = Student::class;

    protected $tenantId;

    public function __construct(Import $import, array $columnMap, array $options)
    {
        parent::__construct($import, $columnMap, $options);

        if (Filament::getTenant()) {
            $this->tenantId = Filament::getTenant()->id;
        }
    }

    /**
     * Create Admission record for the student
     */
    protected function createAdmission(): Admission
    {
        try {
            // Get current academic session
            $currentSession = AcademicSession::where('is_current', true)->first();
            if (!$currentSession) {
                $this->sendErrorNotification(
                    "No Active Session",
                    "No current academic session found. Please set an active academic session."
                );
                throw new RowImportFailedException("No current academic session found.");
            }

            // Look up state by name
            $state = State::where('name', $this->data['state'])->first();
            if (!$state) {
                throw new RowImportFailedException("State '{$this->data['state']}' not found.");
            }

            // Look up LGA by name within the state
            $lga = Lga::where('state_id', $state->id)
                ->where('name', $this->data['lga'])
                ->first();
            if (!$lga) {
                throw new RowImportFailedException("LGA '{$this->data['lga']}' not found in {$state->name}.");
            }

            // Look up admission status (type: admission)
            $status = Status::where('type', 'admission')
                ->where('name', 'approved') // Default to approved for imported students
                ->first();
            if (!$status) {
                throw new RowImportFailedException("Admission status 'approved' not found.");
            }

            // Generate admission number if not provided
            $admissionNumber = $this->data['admission_number'] ?? $this->generateAdmissionNumber();

            $admissionData = [
                'school_id' => $this->tenantId,
                'academic_session_id' => $currentSession->id,
                'session' => $currentSession->name,

                // Personal Information
                'first_name' => $this->data['first_name'],
                'last_name' => $this->data['last_name'],
                'middle_name' => $this->data['middle_name'] ?? null,
                'date_of_birth' => $this->data['date_of_birth'],
                'gender' => $this->data['gender'] ?? 'Not Specified',
                'phone_number' => $this->data['phone_number'] ?? null,
                'email' => $this->data['email'] ?? null,

                // Location Information
                'state_id' => $state->id,
                'lga_id' => $lga->id,
                'address' => $this->data['address'] ?? 'Not Provided',

                // Additional Information
                'religion' => $this->data['religion'] ?? 'Not Specified',
                'blood_group' => $this->data['blood_group'] ?? null,
                'genotype' => $this->data['genotype'] ?? null,

                // School Information
                'previous_school_name' => $this->data['previous_school_name'] ?? null,
                'previous_class' => $this->data['previous_class'] ?? null,
                'admitted_date' => $this->data['admitted_date'] ?? now(),
                'application_date' => $this->data['application_date'] ?? now(),
                'admission_number' => $admissionNumber,
                'status_id' => $status->id,

                // Guardian Information
                'guardian_name' => $this->data['guardian_name'] ?? null,
                'guardian_relationship' => $this->data['guardian_relationship'] ?? null,
                'guardian_phone_number' => $this->data['guardian_phone_number'] ?? null,
                'guardian_email' => $this->data['guardian_email'] ?? null,
                'guardian_address' => $this->data['guardian_address'] ?? null,

                // Emergency Contact
                'emergency_contact_name' => $this->data['emergency_contact_name'] ?? null,
                'emergency_contact_relationship' => $this->data['emergency_contact_relationship'] ?? null,
                'emergency_contact_phone_number' => $this->data['emergency_contact_phone_number'] ?? null,
                'emergency_contact_email' => $this->data['emergency_contact_email'] ?? null,

                // Health Information
                'disability_type' => $this->data['disability_type'] ?? null,
                'disability_description' => $this->data['disability_description'] ?? null,

                // Documents
                'passport_photograph' => $this->data['passport_photograph'] ?? null,

                // System Fields
                'created_by' => auth()->id(),
            ];

            // Create the admission record
            $admission = new Admission($admissionData);

            $admission->school_id = $this->tenantId;

            $admission->save();

            $this->sendSuccessNotification(
                "Admission Created",
                "Successfully created admission record with number {$admission->admission_number}"
            );

            return $admission;
        } catch (RowImportFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in createAdmission:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $this->data
            ]);
            $this->sendErrorNotification(
                "Admission Creation Failed",
                "Failed to create admission record: {$e->getMessage()}"
            );
            throw new RowImportFailedException("Failed to create admission: {$e->getMessage()}");
        }
    }

    public static function getColumns(): array
    {
        return [
            // Basic Information (For both Student and Admission)
            ImportColumn::make('first_name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->guess(['firstname', 'first', 'given_name']),

            ImportColumn::make('last_name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->guess(['lastname', 'last', 'surname', 'family_name']),

            ImportColumn::make('middle_name')
                ->rules(['nullable', 'string', 'max:255'])
                ->guess(['middlename', 'middle']),

            ImportColumn::make('date_of_birth')
                ->requiredMapping()
                ->rules(['required', 'date'])
                ->guess(['dob', 'birth_date', 'birthdate']),

            ImportColumn::make('phone_number')
                ->rules(['nullable', 'string'])
                ->guess(['phone', 'contact_number', 'mobile']),

            // Class Information
            ImportColumn::make('class_room')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->guess(['class', 'classroom', 'class_name']),

            // Status
            ImportColumn::make('status')
                ->label('Status')
                ->relationship(
                    resolveUsing: function (string $state): ?Status {
                        return Status::query()
                            ->where('type', 'student')
                            ->where(function ($query) use ($state) {
                                $query->where('name', $state)
                                    ->orWhere('id', $state);
                            })
                            ->first();
                    }
                )
                ->requiredMapping()
                ->rules(['required']),

            // Admission-specific Information
            ImportColumn::make('email')
                ->rules(['nullable', 'email']),

            ImportColumn::make('state')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('lga')
                ->requiredMapping()
                ->rules(['required', 'string']),

            ImportColumn::make('address')
                ->rules(['nullable', 'string']),

            ImportColumn::make('gender')
                ->rules(['nullable', 'string']),

            ImportColumn::make('religion')
                ->rules(['nullable', 'string']),

            ImportColumn::make('blood_group')
                ->rules(['nullable', 'string']),

            ImportColumn::make('genotype')
                ->rules(['nullable', 'string']),

            ImportColumn::make('admission_number')
                ->rules(['nullable', 'string']),

            ImportColumn::make('previous_school_name')
                ->rules(['nullable', 'string']),

            ImportColumn::make('previous_class')
                ->rules(['nullable', 'string']),

            ImportColumn::make('admitted_date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('application_date')
                ->rules(['nullable', 'date']),

            ImportColumn::make('guardian_name')
                ->rules(['nullable', 'string']),

            ImportColumn::make('guardian_phone_number')
                ->rules(['nullable', 'string']),

            ImportColumn::make('guardian_email')
                ->rules(['nullable', 'email']),

            ImportColumn::make('guardian_address')
                ->rules(['nullable', 'string']),

            ImportColumn::make('guardian_relationship')
                ->rules(['nullable', 'string']),

            ImportColumn::make('emergency_contact_name')
                ->rules(['nullable', 'string']),

            ImportColumn::make('emergency_contact_phone_number')
                ->rules(['nullable', 'string']),

            ImportColumn::make('emergency_contact_email')
                ->rules(['nullable', 'email']),

            ImportColumn::make('emergency_contact_relationship')
                ->rules(['nullable', 'string']),

            ImportColumn::make('disability_type')
                ->rules(['nullable', 'string']),

            ImportColumn::make('disability_description')
                ->rules(['nullable', 'string']),
        ];
    }

    /**
     * Check if a student already exists based on multiple criteria
     */
    protected function studentExists(): bool
    {
        try {
            Log::info('Checking for existing student with criteria:', [
                'first_name' => $this->data['first_name'],
                'last_name' => $this->data['last_name'],
                'date_of_birth' => $this->data['date_of_birth'],
                'school_id' => $this->tenantId
            ]);

            $query = Student::query()
                ->where('school_id', $this->tenantId)
                ->where('first_name', $this->data['first_name'])
                ->where('last_name', $this->data['last_name'])
                ->where('date_of_birth', $this->data['date_of_birth']);

            // Only include phone number in the check if it's provided
            if (!empty($this->data['phone_number'])) {
                $query->where('phone_number', $this->data['phone_number']);
            }

            $existingStudent = $query->first();

            if ($existingStudent) {
                Log::warning('Found existing student:', [
                    'student_id' => $existingStudent->id,
                    'admission_number' => $existingStudent->admission_number
                ]);

                // Build a descriptive message about why we think this is a duplicate
                $message = "Student already exists with following matching details:\n";
                $message .= "- Name: {$this->data['first_name']} {$this->data['last_name']}\n";
                $message .= "- Date of Birth: {$this->data['date_of_birth']}\n";

                if (!empty($this->data['phone_number'])) {
                    $message .= "- Phone Number: {$this->data['phone_number']}\n";
                }
                $this->sendWarningNotification(
                    "Duplicate Student",
                    "Student already exists with name: {$this->data['first_name']} {$this->data['last_name']}, " .
                    "DOB: {$this->data['date_of_birth']}"
                );
                throw new RowImportFailedException($message);
            }

            Log::info('No existing student found - proceeding with creation');
            return false;
        } catch (RowImportFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in studentExists check:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function saveRecord(): void
    {
        try {
            Log::info('SaveRecord called - using custom save logic');

            // The record is already saved in resolveRecord()
            // We don't want to do anything here
            // This prevents the default behavior of trying to save all imported columns

        } catch (\Exception $e) {
            Log::error('Error in saveRecord:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new RowImportFailedException($e->getMessage());
        }
    }

    public function resolveRecord(): ?Model
    {
        try {
            Log::info('Starting resolveRecord', [
                'data' => $this->data,
                'tenant_id' => $this->tenantId
            ]);

            // Check for existing student using composite criteria
            $this->studentExists();

            // Look up the classroom by name
            $classroom = ClassRoom::query()
                ->where('school_id', $this->tenantId)
                ->where(function ($query) {
                    $query->where('name', $this->data['class_room'])
                        ->orWhere('slug', strtolower($this->data['class_room']));
                })
                ->first();

            if (!$classroom) {
                $this->sendErrorNotification("Classroom Not Found", "Classroom '{$this->data['class_room']}' not found.");
                throw new RowImportFailedException("Classroom '{$this->data['class_room']}' not found.");
            }

            Log::info('Found classroom', [
                'classroom_id' => $classroom->id,
                'classroom_name' => $classroom->name
            ]);

            // Use transaction to ensure both admission and student are created or neither
            $student = DB::transaction(function () use ($classroom) {
                // Create admission first
                Log::info('Creating admission record...');
                $admission = $this->createAdmission();
                Log::info('Admission created', ['admission_id' => $admission->id]);

                // Create student with admission_id
                $studentData = [
                    'school_id' => $this->tenantId,
                    'admission_id' => $admission->id,
                    'admission_number' => $admission->admission_number,
                    'class_room_id' => $classroom->id,
                    'status_id' => $this->data['status'],
                    'first_name' => $this->data['first_name'],
                    'last_name' => $this->data['last_name'],
                    'middle_name' => $this->data['middle_name'] ?? null,
                    'date_of_birth' => $this->data['date_of_birth'],
                    'phone_number' => $this->data['phone_number'] ?? null,
                    'profile_picture' => $this->data['profile_picture'] ?? null,
                    'created_by' => auth()->id(),
                    'user_id' => auth()->id(),
                ];

                Log::info('Creating student record with data:', [
                    'student_data' => $studentData
                ]);

                $student = new Student($studentData);
                $student->save();

                $this->sendSuccessNotification(
                    "Student Created",
                    "Successfully created student {$student->first_name} {$student->last_name} with admission number {$student->admission_number}"
                );
                Log::info('Student created successfully', [
                    'student_id' => $student->id,
                    'admission_id' => $admission->id
                ]);

                return $student;
            });

            // Set the record on the importer
            $this->record = $student;

            return $student;
        } catch (RowImportFailedException $e) {
            $this->sendErrorNotification("Import Failed", $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Error in resolveRecord:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'tenant_id' => $this->tenantId,
                'data' => $this->data
            ]);
            $this->sendErrorNotification(
                "Error Creating Student",
                "An error occurred while creating the student record. Please check the logs for details."
            );
            throw new RowImportFailedException($e->getMessage());
        }
    }

    /**
     * Helper method to send success notifications
     */
    protected function sendSuccessNotification(string $title, string $message): void
    {
        Notification::make()
            ->success()
            ->title($title)
            ->body($message)
            ->send();
    }

    /**
     * Helper method to send error notifications
     */
    protected function sendErrorNotification(string $title, string $message): void
    {
        Notification::make()
            ->danger()
            ->title($title)
            ->body($message)
            ->duration(10000) // 10 seconds
            ->send();
    }

    /**
     * Helper method to send warning notifications
     */
    protected function sendWarningNotification(string $title, string $message): void
    {
        Notification::make()
            ->warning()
            ->title($title)
            ->body($message)
            ->duration(5000) // 5 seconds
            ->send();
    }

    /**
     * Generate a unique admission number
     */
    protected function generateAdmissionNumber(): string
    {
        $prefix = date('Y');
        $lastStudent = Student::where('school_id', $this->tenantId)
            ->where('admission_number', 'LIKE', $prefix . '%')
            ->orderBy('admission_number', 'desc')
            ->first();

        if ($lastStudent) {
            $lastNumber = (int) substr($lastStudent->admission_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function beforeImport(): void
    {
        if (!$this->tenantId) {
            $this->tenantId = $this->options['tenant_id'] ?? null;
        }

        if (!$this->tenantId) {
            throw new RowImportFailedException('School context not found.');
        }
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import completed successfully!';
    }

    public static function getFailedNotificationBody(Import $import): string 
    {
        return 'Some records failed to import. Please check the error report.';
    }

    /**
     * Configure import notifications
     */
    public static function getNotificationOptions(): array
    {
        return [
            'starting' => [
                'title' => 'Starting import...',
                'body' => 'The student import process has begun.',
            ],
            'progress' => [
                'title' => 'Import in progress...',
                'body' => fn(Import $import) => 
                    "Processed {$import->processed_rows} of {$import->total_rows} rows.",
            ],
            'completed' => [
                'title' => 'Import completed',
                'body' => fn(Import $import) => 
                    "Successfully imported {$import->successful_rows} students." . 
                    ($import->failed_rows ? " {$import->failed_rows} rows failed." : ''),
                'duration' => 10000,
            ],
            'failed' => [
                'title' => 'Import failed',
                'body' => fn(Import $import) => 
                    "Import failed with {$import->failed_rows} errors. " .
                    "Please check the error report for details.",
                'duration' => 15000,
            ],
        ];
    }

    /**
     * Configure the failed rows report
     */
    public static function getFailedRowsReport(Import $import): array
    {
        return [
            'headers' => [
                'row' => 'Row',
                'error' => 'Error Message',
                'student' => 'Student Name',
            ],
            'rows' => $import->failures->map(fn ($failure) => [
                'row' => $failure->row_number,
                'error' => $failure->message,
                'student' => $failure->values['first_name'] ?? '' . ' ' . $failure->values['last_name'] ?? '',
            ])->toArray(),
        ];
    }
}

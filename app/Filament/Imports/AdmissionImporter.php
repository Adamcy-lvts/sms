<?php

namespace App\Filament\Imports;

use App\Models\Lga;
use App\Models\State;
use App\Models\Status;
use App\Models\Admission;
use App\Models\ClassRoom;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Imports\Importer;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Services\AdmissionNumberGenerator;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Models\Import;
use Filament\Actions\Imports\Exceptions\RowImportFailedException;

class AdmissionImporter extends Importer
{
    protected static ?string $model = Admission::class;

    protected $tenantId;
    protected $admissionNumberGenerator;

    // Add this property to store state ID temporarily
    protected static ?int $cachedStateId = null;

    public function __construct(Import $import, array $columnMap, array $options)
    {
        parent::__construct($import, $columnMap, $options);

        if (Filament::getTenant()) {
            $this->tenantId = Filament::getTenant()->id;
        }

        $this->admissionNumberGenerator = new AdmissionNumberGenerator();
    }

    /**
     * Generate a unique admission number using the AdmissionNumberGenerator service
     */
    protected function generateAdmissionNumber(): string
    {
        // Use the AdmissionNumberGenerator service like in CreateAdmission page
        return $this->admissionNumberGenerator->generate();
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
                ->where('name', 'pending') // Default to approved for imported students
                ->first();
            if (!$status) {
                throw new RowImportFailedException("Admission status 'approved' not found.");
            }


            $admissionData = [
                'school_id' => $this->tenantId,
                'academic_session_id' => $currentSession->id,
                'session' => $currentSession->name,

                // Personal Information
                'first_name' => $this->data['first_name'],
                'last_name' => $this->data['last_name'],
                'middle_name' => $this->data['middle_name'] ?? null,
                'date_of_birth' =>  $this->data['date_of_birth'],
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
                'admission_number' => null,
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
                ->rules(['required', 'date']),

            ImportColumn::make('phone_number')
                ->rules(['nullable', 'string'])
                ->guess(['phone', 'contact_number', 'mobile']),

            // Class Information
            ImportColumn::make('class_room')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->guess(['class', 'classroom', 'class_name']),

            // Status
            // ImportColumn::make('status')
            //     ->label('Status')
            //     ->relationship(
            //         resolveUsing: function (string $state): ?Status {
            //             return Status::query()
            //                 ->where('type', 'student')
            //                 ->where(function ($query) use ($state) {
            //                     $query->where('name', $state)
            //                         ->orWhere('id', $state);
            //                 })
            //                 ->first();
            //         }
            //     )
            //     ->requiredMapping(),

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

    public function resolveRecord(): ?Model
    {
        try {
            Log::info('Starting resolveRecord', [
                'data' => $this->data,
                'tenant_id' => $this->tenantId
            ]);


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

            // Use transaction to ensure both admission are created 

            $admission = $this->createAdmission();


            $this->sendSuccessNotification(
                "admission Created",
                "Successfully created admission {$admission->first_name} {$admission->last_name} with admission number {$admission->admission_number}"
            );


            // Set the record on the importer
            $this->record = $admission;

            return $admission;
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
                "Error Creating admission",
                "An error occurred while creating the admission record. Please check the logs for details."
            );
            throw new RowImportFailedException($e->getMessage());
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

    public static function getCompletedNotificationBody(Import $import): string
    {
        return 'Import completed successfully!';
    }

    public static function getFailedNotificationBody(Import $import): string
    {
        return 'Some records failed to import. Please check the error report.';
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
}

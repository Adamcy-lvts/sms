<?php

namespace App\Imports;

use Exception;
use Throwable;
use Carbon\Carbon;
use App\Models\Lga;
use App\Models\State;
use App\Models\Status;
use App\Models\Student;
use App\Models\Admission;
use App\Models\ClassRoom;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Validators\Failure;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class StudentAdmissionImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure,
    WithChunkReading
// WithBatchInserts
{
    protected $school;
    protected $academic_session_id;
    protected $errors = [];
    protected $successes = [];
    protected $relationshipCache = [];
    protected $row_number = 0;
    protected $shouldUpdate = false;

    protected $successCount = 0;


    public function __construct()
    {
        $this->school = Filament::getTenant();
        // $this->academic_session_id = $academic_session_id;
    }

    protected function getAcademicSessionId(array $row): int
    {
        // If academic_session is provided in the file
        if (!empty($row['academic_session'])) {
            // Try to find the academic session by name
            $academicSession = AcademicSession::where('school_id', $this->school->id)
                ->where(function ($query) use ($row) {
                    $query->where('name', $row['academic_session'])
                        ->orWhere('name', 'like', '%' . $row['academic_session'] . '%');
                })
                ->first();

            if ($academicSession) {
                return $academicSession->id;
            }

            // If not found, throw an exception
            throw new Exception("Academic session '{$row['academic_session']}' not found");
        }

        // Fallback to current session
        $currentSession = config('app.current_session');

        if (!$currentSession) {
            throw new Exception("No current academic session set");
        }

        return $currentSession->id;
    }


    public function checkForDuplicates(array $row): ?array
    {
        $phoneNumber = $this->sanitizePhoneNumber($row['phone_number']);
        $firstName = $this->sanitizeString($row['first_name']);
        $lastName = $this->sanitizeString($row['last_name']);
        $middleName = $this->sanitizeString($row['middle_name'] ?? null);
        $dateOfBirth = $this->parseDate($row['date_of_birth'], 'date_of_birth');

        // First check: Exact admission number match
        if (!empty($row['admission_number'])) {
            $admissionNumber = strtoupper(trim($row['admission_number']));

            // Check both tables for admission number
            $existingAdmission = Admission::where('school_id', $this->school->id)
                ->where('admission_number', $admissionNumber)
                ->first();

            $existingStudent = Student::where('school_id', $this->school->id)
                ->where('admission_number', $admissionNumber)
                ->first();

            if ($existingAdmission || $existingStudent) {
                return [
                    'student' => $existingStudent ?? $existingAdmission->student,
                    'type' => 'admission_number'
                ];
            }
        }

        // Second check: Combination of name, phone, and DOB
        $query = Student::where('school_id', $this->school->id)
            ->where(function ($q) use ($phoneNumber, $firstName, $lastName, $middleName, $dateOfBirth) {
                // Match by phone number
                $q->where('phone_number', $phoneNumber)
                    // OR match by combination of names and DOB
                    ->orWhere(function ($q) use ($firstName, $lastName, $middleName, $dateOfBirth) {
                        $q->where('first_name', $firstName)
                            ->where('last_name', $lastName)
                            ->where('date_of_birth', $dateOfBirth)
                            ->when($middleName, function ($q) use ($middleName) {
                                $q->where('middle_name', $middleName);
                            });
                    });
            });

        $existingStudent = $query->first();

        if ($existingStudent) {
            return [
                'student' => $existingStudent,
                'type' => 'existing_record'
            ];
        }

        // Third check: Check if we're about to create a duplicate
        $duplicateCheck = Student::where('school_id', $this->school->id)
            ->where(function ($q) use ($firstName, $lastName, $middleName, $dateOfBirth) {
                $q->where([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $dateOfBirth
                ])->when($middleName, function ($q) use ($middleName) {
                    $q->where('middle_name', $middleName);
                });
            })
            ->exists();

        if ($duplicateCheck) {
            throw new Exception("A student with the same name and date of birth already exists");
        }

        return null;
    }


    public function model(array $row)
    {
        $this->row_number++;

        try {
            // Begin transaction
            DB::beginTransaction();

            // Get academic session ID first
            $this->academic_session_id = $this->getAcademicSessionId($row);

            // Validate row data
            $this->validateRowData($row);

            // Process all data first to ensure consistent format
            $processedData = [
                'first_name' => $this->sanitizeString($row['first_name']),
                'last_name' => $this->sanitizeString($row['last_name']),
                'middle_name' => $this->sanitizeString($row['middle_name'] ?? null),
                'phone_number' => $this->sanitizePhoneNumber($row['phone_number']),
                'date_of_birth' => $this->parseDate($row['date_of_birth'], 'date_of_birth'),
                'admission_number' => !empty($row['admission_number']) ? strtoupper(trim($row['admission_number'])) : null,
                'guardian_phone' => $this->sanitizePhoneNumber($row['guardian_phone_number']),
                'emergency_phone' => $this->sanitizePhoneNumber($row['emergency_contact_phone_number']),
                'class_room_id' => $this->getRelationshipId(ClassRoom::class, $row['class_room'], 'Class Room'),
                'status_id' => $this->getRelationshipId(Status::class, $row['status'] ?? 'Active', 'Status'),
                'state_id' => !empty($row['state']) ? $this->getRelationshipId(State::class, $row['state'], 'State') : null,
                'lga_id' => !empty($row['lga']) ? $this->getRelationshipId(Lga::class, $row['lga'], 'LGA') : null,
                'academic_session_id' => $this->academic_session_id
            ];

            // Check for duplicates
            $duplicate = $this->checkForDuplicates($row);

            if ($duplicate) {
                // If updating is not allowed, skip silently
                if (!$this->shouldUpdate) {
                    DB::rollBack();
                    return null;
                }

                // Update existing student if needed
                if ($this->hasChanges($duplicate['student'], $processedData)) {
                    $this->updateExistingStudent($duplicate['student'], $processedData);
                    DB::commit();
                }

                return null;
            }

            // Create admission record
            $admission = new Admission();
            $admission->fill([
                'school_id' => $this->school->id,
                'academic_session_id' => $processedData['academic_session_id'],
                'session' => $row['session'] ?? AcademicSession::find($processedData['academic_session_id'])->name,

                // Personal Information
                'first_name' => $processedData['first_name'],
                'last_name' => $processedData['last_name'],
                'middle_name' => $processedData['middle_name'],
                'date_of_birth' => $processedData['date_of_birth'],
                'gender' => strtolower($row['gender']),
                'address' => $this->sanitizeString($row['address']),
                'phone_number' => $processedData['phone_number'],
                'email' => $this->sanitizeString($row['email'] ?? null),

                // Location Information
                'state_id' => $processedData['state_id'],
                'lga_id' => $processedData['lga_id'],

                // Medical Information
                'religion' => $this->sanitizeString($row['religion'] ?? null),
                'blood_group' => $this->sanitizeString($row['blood_group'] ?? null),
                'genotype' => $this->sanitizeString($row['genotype'] ?? null),

                // Disability Information
                'disability_type' => $this->sanitizeString($row['disability_type'] ?? null),
                'disability_description' => $this->sanitizeString($row['disability_description'] ?? null),

                // Previous Education
                'previous_school_name' => $this->sanitizeString($row['previous_school_name'] ?? null),
                'previous_class' => $this->sanitizeString($row['previous_class'] ?? null),

                // Admission Details
                'application_date' => $this->parseDate($row['application_date'] ?? null, 'application_date'),
                'admitted_date' => $this->parseDate($row['admitted_date'] ?? null, 'admitted_date'),
                'admission_number' => $processedData['admission_number'],
                'status_id' => $processedData['status_id'],

                // Guardian Information
                'guardian_name' => $this->sanitizeString($row['guardian_name']),
                'guardian_relationship' => $this->sanitizeString($row['guardian_relationship']),
                'guardian_phone_number' => $processedData['guardian_phone'],
                'guardian_email' => $this->sanitizeString($row['guardian_email'] ?? null),
                'guardian_address' => $this->sanitizeString($row['guardian_address'] ?? null),
                'guardian_occupation' => $this->sanitizeString($row['guardian_occupation'] ?? null),

                // Emergency Contact
                'emergency_contact_name' => $this->sanitizeString($row['emergency_contact_name']),
                'emergency_contact_relationship' => $this->sanitizeString($row['emergency_contact_relationship']),
                'emergency_contact_phone_number' => $processedData['emergency_phone'],
                'emergency_contact_email' => $this->sanitizeString($row['emergency_contact_email'] ?? null),
                'emergency_contact_address' => $this->sanitizeString($row['emergency_contact_address'] ?? null),

                // System fields
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Validate required fields before saving
            if (empty($admission->guardian_name) || empty($admission->guardian_phone_number)) {
                throw new Exception('Guardian information is required');
            }

            if (empty($admission->emergency_contact_name) || empty($admission->emergency_contact_phone_number)) {
                throw new Exception('Emergency contact information is required');
            }

            // Additional validation for phone numbers
            if (
                !empty($admission->guardian_phone_number) &&
                $admission->guardian_phone_number === $admission->emergency_contact_phone_number
            ) {
                throw new Exception('Guardian and emergency contact must have different phone numbers');
            }

            $admission->save();

            // Before student creation
            Log::info('Attempting to create student record', [
                'row_data' => $row,
                'processed_data' => $processedData,
                'school_id' => $this->school->id,
                'admission_id' => $admission->id ?? null
            ]);

            // Create student record
            $student = new Student();
            $student->fill([
                'school_id' => $this->school->id,
                'admission_id' => $admission->id,
                'class_room_id' => $processedData['class_room_id'],
                'status_id' => $processedData['status_id'],
                'created_by' => auth()->id(),
                'admission_number' => $processedData['admission_number'],
                'first_name' => $processedData['first_name'],
                'last_name' => $processedData['last_name'],
                'middle_name' => $processedData['middle_name'],
                'date_of_birth' => $processedData['date_of_birth'],
                'phone_number' => $processedData['phone_number'],
            ]);

            // Right before save
            Log::info('About to save student', [
                'student_attributes' => $student->getAttributes()
            ]);
            $student->save();
            // Instead of adding individual success messages, just increment the counter
            $this->successCount++;

            DB::commit();



            // DB::commit();
            // return $student;
        } catch (Exception $e) {
            DB::rollBack();

            // Just store the error without row number
            // if (!str_contains($e->getMessage(), 'Duplicate')) {
            //     $this->errors[] = $e->getMessage();
            // }

            // Include row number in the error message
            if (!str_contains($e->getMessage(), 'Duplicate')) {
                $this->errors[] = "Row {$this->row_number}: {$e->getMessage()}";
            }

            return null;
        }
    }


    protected function updateExistingStudent(Student $student, array $row, $classRoomId, $statusId)
    {
        // Update admission record first
        $student->admission->update([
            'academic_session_id' => $this->academic_session_id,
            'session' => $row['session'] ?? null,
            'first_name' => $this->sanitizeString($row['first_name']),
            'last_name' => $this->sanitizeString($row['last_name']),
            'middle_name' => $this->sanitizeString($row['middle_name'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'], 'date_of_birth'),
            'gender' => strtolower($row['gender']),
            'address' => $this->sanitizeString($row['address']),
            'phone_number' => $this->sanitizePhoneNumber($row['phone_number']),
            'email' => $this->sanitizeString($row['email'] ?? null),
            'state_id' => !empty($row['state']) ? $this->getRelationshipId(State::class, $row['state'], 'State') : null,
            'lga_id' => !empty($row['lga']) ? $this->getRelationshipId(Lga::class, $row['lga'], 'LGA') : null,
            'religion' => $this->sanitizeString($row['religion'] ?? null),
            'blood_group' => $this->sanitizeString($row['blood_group'] ?? null),
            'genotype' => $this->sanitizeString($row['genotype'] ?? null),
            'disability_type' => $this->sanitizeString($row['disability_type'] ?? null),
            'disability_description' => $this->sanitizeString($row['disability_description'] ?? null),
            'guardian_name' => $this->sanitizeString($row['guardian_name']),
            'guardian_relationship' => $this->sanitizeString($row['guardian_relationship']),
            'guardian_phone_number' => $this->sanitizePhoneNumber($row['guardian_phone_number']),
            'guardian_email' => $this->sanitizeString($row['guardian_email'] ?? null),
            'guardian_address' => $this->sanitizeString($row['guardian_address'] ?? null),
            'emergency_contact_name' => $this->sanitizeString($row['emergency_contact_name']),
            'emergency_contact_relationship' => $this->sanitizeString($row['emergency_contact_relationship']),
            'emergency_contact_phone_number' => $this->sanitizePhoneNumber($row['emergency_contact_phone_number']),
            'emergency_contact_email' => $this->sanitizeString($row['emergency_contact_email'] ?? null),
        ]);

        // Then update student record
        $student->update([
            'class_room_id' => $classRoomId,
            'status_id' => $statusId,
            'first_name' => $this->sanitizeString($row['first_name']),
            'last_name' => $this->sanitizeString($row['last_name']),
            'middle_name' => $this->sanitizeString($row['middle_name'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'], 'date_of_birth'),
            'phone_number' => $this->sanitizePhoneNumber($row['phone_number']),
            'identification_number' => $this->sanitizeString($row['identification_number'] ?? null),
        ]);
    }
    protected function getRelationshipId($model, $name, $columnName)
    {
        if (empty($name)) {
            throw new Exception("$columnName cannot be empty");
        }

        $cacheKey = $model . strtolower($name);

        if (!isset($this->relationshipCache[$cacheKey])) {
            if (method_exists($model, 'school')) {
                $record = $model::where('school_id', $this->school->id)
                    ->where(function ($query) use ($name) {
                        $query->where('name', $name)
                            ->orWhere('name', strtolower($name))
                            ->orWhere('name', strtoupper($name))
                            ->orWhere('name', ucfirst(strtolower($name)));
                    })
                    ->first();
            } else {
                $record = $model::where(function ($query) use ($name) {
                    $query->where('name', $name)
                        ->orWhere('name', strtolower($name))
                        ->orWhere('name', strtoupper($name))
                        ->orWhere('name', ucfirst(strtolower($name)));
                })->first();
            }

            if (!$record) {
                throw new Exception("$columnName '$name' not found in the system");
            }

            $this->relationshipCache[$cacheKey] = $record->id;
        }

        return $this->relationshipCache[$cacheKey];
    }

    protected function parseDate($value, $fieldName): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            if ($value instanceof \DateTime) {
                return Carbon::instance($value)->format('Y-m-d');
            }

            if (is_numeric($value)) {
                return Carbon::instance(Date::excelToDateTimeObject($value))->format('Y-m-d');
            }

            // Try multiple date formats
            $formats = ['Y-m-d', 'd-m-Y', 'm/d/Y', 'd/m/Y', 'Y/m/d'];
            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // If no format matches, try general parse
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new Exception("Invalid date format for $fieldName: $value");
        }
    }

    protected function sanitizePhoneNumber($phone): string
    {
        if (empty($phone)) {
            return '';
        }

        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Handle Nigerian phone numbers
        if (strlen($phone) === 10) {
            $phone = '0' . $phone;
        } elseif (strlen($phone) === 13 && str_starts_with($phone, '234')) {
            $phone = '0' . substr($phone, 3);
        } elseif (strlen($phone) === 14 && str_starts_with($phone, '+234')) {
            $phone = '0' . substr($phone, 4);
        }

        return $phone;
    }

    protected function sanitizeString($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Remove multiple spaces and trim
        $value = preg_replace('/\s+/', ' ', trim($value));

        // Convert to UTF-8 if needed
        if (!mb_check_encoding($value, 'UTF-8')) {
            $value = mb_convert_encoding($value, 'UTF-8', mb_detect_encoding($value));
        }

        return $value;
    }


    public function rules(): array
    {
        return [
            'first_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'  // Only letters, spaces, and hyphens
            ],
            'last_name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'middle_name' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[\pL\s\-]+$/u'
            ],
            'date_of_birth' => ['required'],
            'gender' => [
                'required',
                Rule::in(['male', 'female', 'other', 'Male', 'Female', 'Other'])
            ],
            'address' => ['required', 'string', 'max:500'],
            'phone_number' => [
                'required',
                'string',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                'max:15'
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'state' => [
                'nullable',
                'string',
                Rule::exists('states', 'name')
            ],
            'lga' => [
                'nullable',
                'string',
                Rule::exists('lgas', 'name')
            ],
            'class_room' => [
                'required',
                'string',
                Rule::exists('class_rooms', 'name')
                    ->where('school_id', $this->school->id)
            ],
            'admission_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('admissions', 'admission_number')
                    ->where('school_id', $this->school->id)
            ],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_relationship' => ['required', 'string', 'max:50'],
            'guardian_phone_number' => [
                'required',
                'string',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                'max:15'
            ],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_relationship' => ['required', 'string', 'max:50'],
            'emergency_contact_phone_number' => [
                'required',
                'string',
                'regex:/^([0-9\s\-\+\(\)]*)$/',
                'min:10',
                'max:15'
            ],
        ];
    }

    public function customValidationMessages()
    {
        return [
            'first_name.required' => 'First name is required',
            'first_name.regex' => 'First name can only contain letters, spaces, and hyphens',
            'last_name.required' => 'Last name is required',
            'last_name.regex' => 'Last name can only contain letters, spaces, and hyphens',
            'middle_name.regex' => 'Middle name can only contain letters, spaces, and hyphens',
            'date_of_birth.required' => 'Date of birth is required',
            'gender.required' => 'Gender is required',
            'gender.in' => 'Gender must be male, female, or other',
            'phone_number.regex' => 'Invalid phone number format',
            'phone_number.min' => 'Phone number must be at least 10 digits',
            'guardian_phone_number.regex' => 'Invalid guardian phone number format',
            'emergency_contact_phone_number.regex' => 'Invalid emergency contact phone number format',
            'class_room.required' => 'Class room is required',
            'class_room.exists' => 'Invalid class room name for this school',
            'admission_number.unique' => 'Admission number already exists in this school',
            'admission_number.max' => 'Admission number cannot exceed 20 characters',
            'state.exists' => 'Invalid state name',
            'lga.exists' => 'Invalid LGA name',
            'email.email' => 'Invalid email format',
            'guardian_name.required' => 'Guardian name is required',
            'guardian_relationship.required' => 'Guardian relationship is required',
            'guardian_phone_number.required' => 'Guardian phone number is required',
            'emergency_contact_name.required' => 'Emergency contact name is required',
            'emergency_contact_relationship.required' => 'Emergency contact relationship is required',
            'emergency_contact_phone_number.required' => 'Emergency contact phone number is required',
            'address.required' => 'Address is required',
            'address.max' => 'Address cannot exceed 500 characters',
        ];
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function onError(Throwable $e)
    {
        $rowNumber = $this->row_number ?: 'Unknown';
        $this->errors[] = "Row {$rowNumber}: {$e->getMessage()}";
        Log::error("Student Import Error", [
            'row' => $rowNumber,
            'error' => $e->getMessage(),
            'school_id' => $this->school->id
        ]);
    }

    // public function onFailure(Failure ...$failures)
    // {
    //     // Collect all validation errors into a single message
    //     $errorMessages = collect($failures)
    //         ->map(fn($failure) => collect($failure->errors())->join(', '))
    //         ->unique()
    //         ->join('; ');

    //     if ($errorMessages) {
    //         $this->errors[] = "Validation failed: " . $errorMessages;
    //     }

    //     Log::warning("Student Import Validation Failure", [
    //         'errors' => $errorMessages,
    //         'school_id' => $this->school->id
    //     ]);
    // }

    public function onFailure(Failure ...$failures)
    {
        // Collect all validation errors into a single message with row numbers
        $errorMessages = collect($failures)
            ->map(function ($failure) {
                $errors = collect($failure->errors())->join(', ');
                return "Row {$failure->row()}: {$errors}";
            })
            ->unique()
            ->join('; ');

        if ($errorMessages) {
            $this->errors[] = "Validation failed - {$errorMessages}";
        }

        Log::warning("Student Import Validation Failure", [
            'errors' => $errorMessages,
            'school_id' => $this->school->id
        ]);
    }

    public function getErrors(): array
    {
        return array_unique($this->errors);
    }

    public function getSuccesses(): array
    {
        return $this->successCount > 0
            ? ["Successfully imported {$this->successCount} student(s)"]
            : [];
    }

    protected function validateRowData(array $row)
    {
        // Keep only essential academic session validation
        if (!empty($row['academic_session'])) {
            $academicSession = AcademicSession::where('school_id', $this->school->id)
                ->where('name', $row['academic_session'])
                ->exists();

            if (!$academicSession) {
                throw new Exception("Invalid academic session: {$row['academic_session']}");
            }
        }

        // Optional: Keep minimal guardian validation if needed
        if (empty($row['guardian_name']) || empty($row['guardian_phone_number'])) {
            throw new Exception('Basic guardian information (name and phone) is required');
        }

        // Optional: Keep minimal emergency contact validation if needed
        if (empty($row['emergency_contact_name']) || empty($row['emergency_contact_phone_number'])) {
            throw new Exception('Basic emergency contact information (name and phone) is required');
        }
    }
}

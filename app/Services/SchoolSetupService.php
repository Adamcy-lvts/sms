<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Term;
use App\Models\Staff;
use App\Models\School;
use App\Models\Status;
use App\Models\Subject;
use App\Models\Template;
use App\Models\ClassRoom;
use App\Models\Designation;
use App\Models\PaymentPlan;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Models\ActivityType;
use App\Models\GradingScale;
use App\Models\PaymentMethod;
use App\Models\AssessmentType;
use App\Models\SchoolSettings;
use App\Models\AcademicSession;
use App\Models\BehavioralTrait;
use App\Models\TemplateVariable;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;
use App\Notifications\TrialExpirationNotification;

class SchoolSetupService
{
    public function setup(School $school, array $data, Authenticatable $user): void
    {
        try {

            // Create academic session and terms
            $this->setupAcademicPeriod($school);

            // Create school calendar events (holidays and breaks)
            $this->setupSchoolCalendar($school);

            // Create grading scales
            $this->createGradingScales($school);

            // Create assessment types
            $this->createAssessmentTypes($school);

            // Create activity types
            $this->createActivityTypes($school);

            // Create behavioral traits (add this line)
            $this->createBehavioralTraits($school);

            // Create designations  
            $this->createDesignations($school);

            // Create staff
            $this->createStaff($data, $school, $user);

            // Set up payment methods
            $this->setupPaymentMethods($school);

            // Set up report templates
            $this->setupReportTemplates($school);

            // Create default admission letter templates and variables
            $this->createDefaultAdmissionTemplate($school);

              // Add new setup methods
              $this->setupDefaultPaymentTypes($school);
              $this->setupDefaultPaymentPlans($school);

            // Create subjects based on toggles
            if ($data['create_subjects'] ?? false) {
                $this->createSubjects($school, $data['create_islamic_subjects'] ?? false);
            }

            // Create classes if enabled
            if ($data['create_classes'] ?? false) {
                $this->createClasses(
                    $school,
                    $data['create_nursery'] ?? false,
                    $data['create_primary'] ?? false,
                    $data['create_secondary'] ?? false,
                    $data['class_sections'] ?? 'A',
                    $data['class_capacity'] ?? 40
                );
            }

            // Set up trial subscription only if plan_id is provided
            if (!empty($data['plan_id'])) {
                $plan = Plan::find($data['plan_id']);
                if ($plan) {
                    $this->setupTrialSubscription($school, $plan, $data['billing_type'] ?? 'monthly');
                }
            }

            // Create default roles
            $roleService = new RoleSetupService();
            $roleService->setupSchoolRoles($school);

        } catch (\Exception $e) {
            Log::error('School setup error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function setupTrialSubscription(School $school, Plan $plan, string $billingType): void
    {
        if (!$plan) {
            return;
        }

        // Create subscription with billing type consideration
        if ($plan->offersTrial()) {
            $subscription = $school->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addDays($plan->trial_period),
                'is_on_trial' => true,
                'trial_ends_at' => now()->addDays($plan->trial_period),
                'billing_cycle' => $billingType,
                'next_payment_date' => now()->addDays($plan->trial_period),
                'is_recurring' => true,
            ]);
        }
    }

    protected function createAssessmentTypes(School $school): void
    {
        // Define default assessment types configuration
        $assessmentTypes = [
            [
                'name' => 'First CA',
                'code' => 'CA1',
                'max_score' => 10,
                'weight' => 10,
                'description' => 'First Continuous Assessment',
                'is_active' => true
            ],
            [
                'name' => 'Second CA',
                'code' => 'CA2',
                'max_score' => 10,
                'weight' => 10,
                'description' => 'Second Continuous Assessment',
                'is_active' => true
            ],
            [
                'name' => 'Third CA',
                'code' => 'CA3',
                'max_score' => 10,
                'weight' => 10,
                'description' => 'Third Continuous Assessment',
                'is_active' => true
            ],
            [
                'name' => 'Examination',
                'code' => 'EXAM',
                'max_score' => 70,
                'weight' => 70,
                'description' => 'End of Term Examination',
                'is_active' => true
            ]
        ];

        // Add assessment types
        foreach ($assessmentTypes as $type) {
            AssessmentType::create([
                'school_id' => $school->id,
                ...$type
            ]);
        }
    }

    protected function setupReportTemplates(School $school): void
    {
        app(DefaultReportTemplateService::class)->createDefaultTemplates($school);
    }

    protected function createClasses(
        School $school,
        bool $includeNursery,
        bool $includePrimary,
        bool $includeSecondary,
        string $sections = 'A',
        int $capacity = 40
    ): void {
        // Get section letters based on selection
        $sectionLetters = match ($sections) {
            'A' => ['A'],
            'AB' => ['A', 'B'],
            'ABC' => ['A', 'B', 'C'],
            default => ['A']
        };

        $classesToCreate = [];

        // Nursery Classes
        if ($includeNursery) {
            $classesToCreate = array_merge($classesToCreate, [
                ['name' => 'Nursery 1'],
                ['name' => 'Nursery 2'],
                ['name' => 'Nursery 3'],
            ]);
        }

        // Primary Classes
        if ($includePrimary) {
            $classesToCreate = array_merge($classesToCreate, [
                ['name' => 'Primary 1'],
                ['name' => 'Primary 2'],
                ['name' => 'Primary 3'],
                ['name' => 'Primary 4'],
                ['name' => 'Primary 5'],
                ['name' => 'Primary 6'],
            ]);
        }

        // Secondary Classes
        if ($includeSecondary) {
            $classesToCreate = array_merge($classesToCreate, [
                ['name' => 'JSS 1'],
                ['name' => 'JSS 2'],
                ['name' => 'JSS 3'],
                ['name' => 'SSS 1'],
                ['name' => 'SSS 2'],
                ['name' => 'SSS 3'],
            ]);
        }

        // Create classes with sections
        foreach ($classesToCreate as $classData) {
            foreach ($sectionLetters as $section) {
                $className = "{$classData['name']} {$section}";

                ClassRoom::create([
                    'school_id' => $school->id,
                    'name' => $className,
                    'slug' => Str::slug($className),
                    'capacity' => $capacity,
                ]);
            }
        }
    }

    /**
     * Set up academic session and terms for 2024/2025
     */
    protected function setupAcademicPeriod(School $school): void
    {
        DB::transaction(function () use ($school) {
            // Create Academic Session
            $session = AcademicSession::create([
                'school_id' => $school->id,
                'name' => '2024/2025',
                'start_date' => '2024-09-09', // First term start
                'end_date' => '2025-09-07',   // Before next session starts
                'is_current' => true,
            ]);

            // Create Terms according to calendar
            $terms = [
                [
                    'name' => 'First Term',
                    'start_date' => '2024-09-09',
                    'end_date' => '2024-12-13',
                    'is_current' => true,
                ],
                [
                    'name' => 'Second Term',
                    'start_date' => '2025-01-06',
                    'end_date' => '2025-03-28',
                    'is_current' => false,
                ],
                [
                    'name' => 'Third Term',
                    'start_date' => '2025-04-28',
                    'end_date' => '2025-08-01',
                    'is_current' => false,
                ]
            ];

            // Create each term
            foreach ($terms as $termData) {
                Term::create([
                    'school_id' => $school->id,
                    'academic_session_id' => $session->id,
                    ...$termData
                ]);
            }
        });
    }

    /**
     * Setup school calendar with holidays and breaks
     */
    protected function setupSchoolCalendar(School $school): void
    {
        DB::transaction(function () use ($school) {
            // Public Holidays
            $holidays = [
                [
                    'title' => 'Eid-Fitr',
                    'start_date' => '2025-03-31',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Good Friday',
                    'start_date' => '2025-04-18',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Easter Sunday',
                    'start_date' => '2025-04-20',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Easter Monday',
                    'start_date' => '2025-04-21',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Children Day',
                    'start_date' => '2025-05-27',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Eid-El Kabir',
                    'start_date' => '2025-06-07',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Democracy Day',
                    'start_date' => '2025-06-12',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Eid-El Maulud',
                    'start_date' => '2025-09-05',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Independence Day',
                    'start_date' => '2025-10-01',
                    'description' => 'Public Holiday'
                ],
                [
                    'title' => 'Christmas',
                    'start_date' => '2025-12-25',
                    'description' => 'Public Holiday'
                ],
            ];

            // Term Breaks/Vacations
            $breaks = [
                [
                    'title' => 'First Term Break',
                    'start_date' => '2024-12-14',
                    'end_date' => '2025-01-05',
                    'description' => '3 Weeks Break'
                ],
                [
                    'title' => 'Second Term Break',
                    'start_date' => '2025-03-29',
                    'end_date' => '2025-04-27',
                    'description' => '4 Weeks Break'
                ],
                [
                    'title' => 'Third Term Break',
                    'start_date' => '2025-08-02',
                    'end_date' => '2025-09-07',
                    'description' => '5 Weeks Break'
                ],
            ];

            // Create holiday events
            foreach ($holidays as $holiday) {
                SchoolCalendarEvent::create([
                    'school_id' => $school->id,
                    'title' => $holiday['title'],
                    'start_date' => $holiday['start_date'],
                    'end_date' => $holiday['start_date'], // Same day for holidays
                    'description' => $holiday['description'],
                    'type' => 'holiday',
                    'excludes_attendance' => true,
                    'color' => '#FF0000', // Red for holidays
                ]);
            }

            // Create break events
            foreach ($breaks as $break) {
                SchoolCalendarEvent::create([
                    'school_id' => $school->id,
                    'title' => $break['title'],
                    'start_date' => $break['start_date'],
                    'end_date' => $break['end_date'],
                    'description' => $break['description'],
                    'type' => 'break',
                    'excludes_attendance' => true,
                    'color' => '#FFA500', // Orange for breaks
                ]);
            }
        });
    }



    protected function createGradingScales(School $school): void
    {
        $scales = [
            [
                'grade' => 'A',
                'min_score' => 70,
                'max_score' => 100,
                'remark' => 'Excellent',
                'is_active' => true
            ],
            [
                'grade' => 'B',
                'min_score' => 60,
                'max_score' => 69,
                'remark' => 'Very Good',
                'is_active' => true
            ],
            [
                'grade' => 'C',
                'min_score' => 50,
                'max_score' => 59,
                'remark' => 'Good',
                'is_active' => true
            ],
            [
                'grade' => 'D',
                'min_score' => 40,
                'max_score' => 49,
                'remark' => 'Fair',
                'is_active' => true
            ],
            [
                'grade' => 'F',
                'min_score' => 0,
                'max_score' => 39,
                'remark' => 'Failed',
                'is_active' => true
            ]
        ];

        foreach ($scales as $scale) {
            GradingScale::create([
                'school_id' => $school->id,
                'is_active' => true,
                ...$scale
            ]);
        }
    }

    protected function createSubjects(School $school, bool $includeIslamic): void
    {
        // Regular subjects
        $subjects = [
            [
                'name' => 'Mathematics',
                'description' => 'Study of numbers, quantities, shapes and patterns'
            ],
            [
                'name' => 'English Language',
                'description' => 'Study of English language and literature'
            ],
            [
                'name' => 'Basic Science',
                'description' => 'Introduction to scientific concepts and methods'
            ],
            [
                'name' => 'Social Studies',
                'description' => 'Study of society and relationships among people'
            ],
            [
                'name' => 'Business Studies',
                'description' => 'Introduction to business concepts and practices'
            ],
            [
                'name' => 'Agricultural Science',
                'description' => 'Study of agriculture and food production'
            ],
            [
                'name' => 'Physical Education',
                'description' => 'Study of physical fitness and sports'
            ],
            [
                'name' => 'Computer Studies',
                'description' => 'Study of computers and information technology'
            ],
            [
                'name' => 'Civic Education',
                'description' => 'Study of citizenship and civic responsibilities'
            ],
        ];

        // Islamic subjects if enabled
        if ($includeIslamic) {
            $subjects = array_merge($subjects, [
                [
                    'name' => 'Al Quran (القرآن)',
                    'name_ar' => 'القرآن الكريم',
                    'description' => 'Study and memorization of the Holy Quran',
                    'description_ar' => 'دراسة وحفظ القرآن الكريم'
                ],
                [
                    'name' => 'Hadith (حديث)',
                    'name_ar' => 'الحديث النبوي',
                    'description' => 'Study of Prophetic traditions',
                    'description_ar' => 'دراسة الأحاديث النبوية الشريفة'
                ],
                [
                    'name' => 'Fiqh (فقه)',
                    'name_ar' => 'الفقه الإسلامي',
                    'description' => 'Islamic Jurisprudence',
                    'description_ar' => 'دراسة الأحكام الشرعية العملية'
                ],
                [
                    'name' => 'Tauheed (توحيد)',
                    'name_ar' => 'التوحيد',
                    'description' => 'Islamic Monotheism',
                    'description_ar' => 'دراسة العقيدة الإسلامية'
                ],
                [
                    'name' => 'Arabic Language',
                    'name_ar' => 'اللغة العربية',
                    'description' => 'Study of Arabic language',
                    'description_ar' => 'دراسة اللغة العربية'
                ],
            ]);
        }

        // Create each subject
        foreach ($subjects as $subject) {
            Subject::create([
                'school_id' => $school->id,
                'name' => $subject['name'],
                'name_ar' => $subject['name_ar'] ?? null,
                'description' => $subject['description'] ?? null,
                'description_ar' => $subject['description_ar'] ?? null,
                'slug' => Str::slug($subject['name']),
                'is_active' => true
            ]);
        }
    }

    protected function createDesignations(School $school): void
    {
        $designations = [
            ['name' => 'Administrator', 'description' => 'The school administrator'],
            ['name' => 'Principal', 'description' => 'The head of the school'],
            ['name' => 'Vice Principal', 'description' => 'The deputy head of the school'],
            ['name' => 'Head Teacher', 'description' => 'The head of the primary section'],
            ['name' => 'Deputy Head Teacher', 'description' => 'The deputy head of the primary section'],
            ['name' => 'Head of Department', 'description' => 'The head of a department'],
            ['name' => 'Teacher', 'description' => 'A classroom teacher'],
            ['name' => 'Clerk', 'description' => 'A school clerk'],
            ['name' => 'Librarian', 'description' => 'A school librarian'],
            ['name' => 'Security Guard', 'description' => 'A school security guard'],
            ['name' => 'Cleaner', 'description' => 'A school cleaner'],
            ['name' => 'Driver', 'description' => 'A school driver'],
            ['name' => 'Cook', 'description' => 'A school cook'],
            ['name' => 'Gardener', 'description' => 'A school gardener'],
            ['name' => 'Accountant', 'description' => 'A school accountant'],
            ['name' => 'Bursar', 'description' => 'A school bursar'],
            ['name' => 'Secretary', 'description' => 'A school secretary'],
        ];

        foreach ($designations as $designation) {
            Designation::create([
                'school_id' => $school->id,
                'is_active' => true,
                ...$designation
            ]);
        }
    }

    protected function setupPaymentMethods(School $school): void
    {
        // Define available payment methods
        $paymentMethods = [
            [
                'name' => 'Bank Transfer',
                'slug' => 'bank-transfer',
                'description' => 'Payment made through bank transfer',
                'active' => true
            ],
            [
                'name' => 'POS',
                'slug' => 'pos',
                'description' => 'Payment made through POS',
                'active' => true
            ],
            [
                'name' => 'Cash',
                'slug' => 'cash',
                'description' => 'Payment made with cash',
                'active' => true
            ],
            [
                'name' => 'Cheque',
                'slug' => 'cheque',
                'description' => 'Payment made with cheque',
                'active' => true
            ],
        ];

        // Create each payment method for the school
        foreach ($paymentMethods as $method) {
            PaymentMethod::create([
                'school_id' => $school->id,
                ...$method
            ]);
        }
    }

    public function createDefaultAdmissionTemplate(School $school): void
    {
        // Create default variables first
        $this->createDefaultTemplateVariables($school);

        // Then create the template
        Template::create([
            'name' => 'Standard Admission Letter',
            'category' => 'admission_letter',
            'description' => 'Standard template for student admission letters',
            'content' => $this->getDefaultTemplateContent(),
            'is_active' => true,
            'is_default' => true,
            'is_system' => true,
            'school_id' => $school->id,
        ]);
    }

    protected function createDefaultTemplateVariables(School $school): void
    {
        $defaultVariables = [
            [
                'name' => 'admission_full_name',
                'display_name' => 'Full Name',
                'description' => 'Student full name',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'admission.full_name',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'admission_number',
                'display_name' => 'Admission Number',
                'description' => 'Student admission number',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'admission.admission_number',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'class_room_name',
                'display_name' => 'Class Room',
                'description' => 'Student class room',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'admission.class_room_name',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'school_name',
                'display_name' => 'School Name',
                'description' => 'School name',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'school.name',
                'is_system' => true,
                'is_active' => true,
            ],
            [
                'name' => 'staff_principal_name',
                'display_name' => 'Principal Name',
                'description' => 'School principal name',
                'category' => 'admission',
                'field_type' => 'text',
                'mapping' => 'school.principal_name',
                'is_system' => true,
                'is_active' => true,
            ]
        ];

        foreach ($defaultVariables as $variable) {
            $variable['school_id'] = $school->id;
            TemplateVariable::create($variable);
        }
    }

    protected function getDefaultTemplateContent(): array
    {
        return [
            "type" => "doc",
            "content" => [
                [
                    "type" => "paragraph",
                    "attrs" => ["textAlign" => "center"],
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "OFFER OF ADMISSION",
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "attrs" => ["textAlign" => "left"],
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Dear ",
                            "marks" => [["type" => "small"]]
                        ],
                        [
                            "type" => "mergeTag",
                            "attrs" => ["id" => "admission_full_name"],
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "We are pleased to inform you that your application for admission to ",
                            "marks" => [["type" => "small"]]
                        ],
                        [
                            "type" => "mergeTag",
                            "attrs" => ["id" => "school_name"],
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ],
                        [
                            "type" => "text",
                            "text" => " has been successful. You have been offered a place in ",
                            "marks" => [["type" => "small"]]
                        ],
                        [
                            "type" => "mergeTag",
                            "attrs" => ["id" => "class_room_name"],
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ],
                        [
                            "type" => "text",
                            "text" => " for the academic year 2024/2025.",
                            "marks" => [["type" => "small"]]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Your admission number is ",
                            "marks" => [["type" => "small"]]
                        ],
                        [
                            "type" => "mergeTag",
                            "attrs" => ["id" => "admission_number"],
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ],
                        [
                            "type" => "text",
                            "text" => ". Please quote this number in all future correspondence.",
                            "marks" => [["type" => "small"]]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "To secure your place, please complete the registration process by September 1st, 2024. The registration package with all necessary forms and requirements is attached to this letter.",
                            "marks" => [["type" => "small"]]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "attrs" => ["textAlign" => "left"],
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "Yours sincerely,",
                            "marks" => [["type" => "small"]]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "mergeTag",
                            "attrs" => ["id" => "principal_name"],
                            "marks" => [
                                ["type" => "bold"],
                                ["type" => "small"]
                            ]
                        ],
                        [
                            "type" => "text",
                            "text" => "\nPrincipal",
                            "marks" => [["type" => "small"]]
                        ]
                    ]
                ]
            ]
        ];
    }

    public function createStaff(array $data, School $school, Authenticatable $user): Staff
    {
        // Get admin designation
        $designation = Designation::where('school_id', $school->id)
            ->where('name', 'Administrator')
            ->first();

        // Get active status for staff
        $activeStatus = Status::where('type', 'staff')
            ->where('name', 'active')
            ->first();

        // Generate employee ID
        $employeeIdGenerator = new EmployeeIdGenerator();
        $employeeId = $employeeIdGenerator->generate([
            'designation_id' => $designation->id,
            'is_admin' => true
        ]);

        return Staff::create([
            'school_id' => $school->id,
            'user_id' => $user->id,
            'designation_id' => $designation->id,
            'status_id' => $activeStatus->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['admin_email'],
            'hire_date' => now(),
            'employee_id' => $employeeId, // Add the generated employee ID
            'is_admin' => true,
        ]);
    }

    protected function createActivityTypes(School $school): void
    {
        // Define default activity codes for quick lookup
        $defaultActivityCodes = ['FB', 'BB', 'DR', 'SC', 'DB', 'CHS', 'STD', 'CS'];

        // Define all activities in a more compact format
        $activities = [
            'Sports & Athletics' => [
                ['name' => 'Football', 'code' => 'FB', 'description' => 'Football/Soccer participation and performance', 'icon' => 'futbol', 'color' => '#15803d'],
                ['name' => 'Basketball', 'code' => 'BB', 'description' => 'Basketball team participation', 'icon' => 'basketball', 'color' => '#b91c1c'],
                ['name' => 'Track & Field', 'code' => 'TF', 'description' => 'Athletics and track events', 'icon' => 'running', 'color' => '#0369a1'],
                ['name' => 'Swimming', 'code' => 'SW', 'description' => 'Swimming and water sports', 'icon' => 'person-swimming', 'color' => '#0891b2'],
                ['name' => 'Volleyball', 'code' => 'VB', 'description' => 'Volleyball team participation', 'icon' => 'volleyball', 'color' => '#ea580c'],
                ['name' => 'Table Tennis', 'code' => 'TT', 'description' => 'Table tennis participation', 'icon' => 'table-tennis-paddle-ball', 'color' => '#4f46e5'],
                ['name' => 'Badminton', 'code' => 'BM', 'description' => 'Badminton team activities', 'icon' => 'shuttlecock', 'color' => '#7c3aed'],
                ['name' => 'Cricket', 'code' => 'CK', 'description' => 'Cricket team participation', 'icon' => 'baseball-bat-ball', 'color' => '#2563eb'],
                ['name' => 'Hockey', 'code' => 'HK', 'description' => 'Hockey team participation', 'icon' => 'hockey-puck', 'color' => '#dc2626']
            ],
            'Arts & Culture' => [
                ['name' => 'Drama Club', 'code' => 'DR', 'description' => 'Theater and dramatic performances', 'icon' => 'masks-theater', 'color' => '#7c2d12'],
                ['name' => 'Art Club', 'code' => 'ART', 'description' => 'Visual arts and creative activities', 'icon' => 'palette', 'color' => '#be185d'],
                ['name' => 'Music Band', 'code' => 'MB', 'description' => 'School band participation', 'icon' => 'music', 'color' => '#6d28d9'],
                ['name' => 'Dance Club', 'code' => 'DC', 'description' => 'Dance and choreography', 'icon' => 'person-dancing', 'color' => '#db2777'],
                ['name' => 'Photography Club', 'code' => 'PH', 'description' => 'Photography and visual storytelling', 'icon' => 'camera', 'color' => '#0d9488'],
                ['name' => 'Creative Writing', 'code' => 'CW', 'description' => 'Creative writing and literature', 'icon' => 'pen-fancy', 'color' => '#0369a1'],
                ['name' => 'Cultural Dance', 'code' => 'CD', 'description' => 'Traditional and cultural dance', 'icon' => 'person-dancing', 'color' => '#ca8a04'],
                ['name' => 'Film Making', 'code' => 'FM', 'description' => 'Video production and editing', 'icon' => 'film', 'color' => '#be123c']
            ],
            'Academic Clubs' => [
                ['name' => 'Science Club', 'code' => 'SC', 'description' => 'Science experiments and research', 'icon' => 'flask', 'color' => '#059669'],
                ['name' => 'Mathematics Club', 'code' => 'MC', 'description' => 'Advanced mathematics and problem solving', 'icon' => 'calculator', 'color' => '#0891b2'],
                ['name' => 'Debate Club', 'code' => 'DB', 'description' => 'Debating and public speaking', 'icon' => 'comments', 'color' => '#4338ca'],
                ['name' => 'Robotics Club', 'code' => 'RC', 'description' => 'Robotics and programming', 'icon' => 'robot', 'color' => '#7c3aed'],
                ['name' => 'Computer Club', 'code' => 'CC', 'description' => 'Computer programming and technology', 'icon' => 'computer', 'color' => '#0284c7'],
                ['name' => 'Book Club', 'code' => 'BC', 'description' => 'Reading and literature discussion', 'icon' => 'book-open', 'color' => '#9333ea'],
                ['name' => 'Language Club', 'code' => 'LC', 'description' => 'Foreign language learning', 'icon' => 'language', 'color' => '#2563eb'],
                ['name' => 'Chess Club', 'code' => 'CHS', 'description' => 'Chess strategy and tournaments', 'icon' => 'chess', 'color' => '#4b5563'],
                ['name' => 'Electronics Club', 'code' => 'EC', 'description' => 'Electronics and circuitry', 'icon' => 'microchip', 'color' => '#b91c1c']
            ],
            'Leadership & Service' => [
                ['name' => 'Student Council', 'code' => 'SC', 'description' => 'Student leadership and governance', 'icon' => 'users', 'color' => '#1d4ed8'],
                ['name' => 'Community Service', 'code' => 'CS', 'description' => 'Community outreach activities', 'icon' => 'handshake', 'color' => '#166534'],
                ['name' => 'Environmental Club', 'code' => 'EC', 'description' => 'Environmental conservation', 'icon' => 'leaf', 'color' => '#15803d'],
                ['name' => 'Peer Mentoring', 'code' => 'PM', 'description' => 'Peer support and mentoring', 'icon' => 'user-group', 'color' => '#0f766e'],
                ['name' => 'Red Cross Society', 'code' => 'RCS', 'description' => 'First aid and humanitarian services', 'icon' => 'kit-medical', 'color' => '#dc2626'],
                ['name' => 'School Newsletter', 'code' => 'NL', 'description' => 'School publication and journalism', 'icon' => 'newspaper', 'color' => '#0369a1'],
                ['name' => 'Career Club', 'code' => 'CC', 'description' => 'Career guidance and development', 'icon' => 'briefcase', 'color' => '#6d28d9'],
                ['name' => 'Library Assistants', 'code' => 'LA', 'description' => 'Library management and assistance', 'icon' => 'book', 'color' => '#a21caf']
            ]
        ];

        // Create activities in order
        $order = 0;
        foreach ($activities as $category => $categoryActivities) {
            foreach ($categoryActivities as $activity) {
                ActivityType::create([
                    'school_id' => $school->id,
                    'name' => $activity['name'],
                    'code' => $activity['code'],
                    'description' => $activity['description'],
                    'category' => $category,
                    'icon' => $activity['icon'],
                    'color' => $activity['color'],
                    'display_order' => $order++,
                    'is_default' => in_array($activity['code'], $defaultActivityCodes)
                ]);
            }
        }
    }

    protected function createBehavioralTraits(School $school): void
    {
        // Define default behavioral trait codes for important traits
        $defaultTraitCodes = ['ATT', 'CP', 'PUNC', 'NEAT', 'SC', 'RESP', 'COMM', 'TW', 'EFF', 'INT'];

        // Define all behavioral traits with categories
        $traits = [
            'Learning Skills' => [
                ['name' => 'Attentiveness', 'code' => 'ATT', 'description' => 'Ability to focus and pay attention in class', 'weight' => 1.0],
                ['name' => 'Class Participation', 'code' => 'CP', 'description' => 'Level of active participation in class', 'weight' => 1.0],
                ['name' => 'Homework Completion', 'code' => 'HW', 'description' => 'Consistency in completing homework', 'weight' => 1.0],
                ['name' => 'Organization', 'code' => 'ORG', 'description' => 'Ability to organize work and materials', 'weight' => 1.0],
                ['name' => 'Study Skills', 'code' => 'SS', 'description' => 'Effective study habits and techniques', 'weight' => 1.0],
                ['name' => 'Critical Thinking', 'code' => 'CT', 'description' => 'Ability to analyze and solve problems', 'weight' => 1.0],
                ['name' => 'Research Skills', 'code' => 'RS', 'description' => 'Ability to gather and analyze information', 'weight' => 1.0],
                ['name' => 'Note Taking', 'code' => 'NT', 'description' => 'Ability to take and organize notes effectively', 'weight' => 1.0]
            ],
            'Social Skills' => [
                ['name' => 'Cooperation', 'code' => 'COOP', 'description' => 'Works well with others', 'weight' => 1.0],
                ['name' => 'Respect', 'code' => 'RESP', 'description' => 'Shows respect for teachers and peers', 'weight' => 1.0],
                ['name' => 'Communication', 'code' => 'COMM', 'description' => 'Communicates effectively', 'weight' => 1.0],
                ['name' => 'Team Work', 'code' => 'TW', 'description' => 'Works effectively in groups', 'weight' => 1.0],
                ['name' => 'Leadership', 'code' => 'LEAD', 'description' => 'Shows leadership qualities', 'weight' => 1.0],
                ['name' => 'Conflict Resolution', 'code' => 'CR', 'description' => 'Handles conflicts appropriately', 'weight' => 1.0],
                ['name' => 'Empathy', 'code' => 'EMP', 'description' => 'Shows understanding and care for others', 'weight' => 1.0],
                ['name' => 'Cultural Awareness', 'code' => 'CA', 'description' => 'Respects cultural differences', 'weight' => 1.0]
            ],
            'Personal Development' => [
                ['name' => 'Punctuality', 'code' => 'PUNC', 'description' => 'Arrives to class on time', 'weight' => 1.0],
                ['name' => 'Neatness', 'code' => 'NEAT', 'description' => 'Maintains neat appearance and work', 'weight' => 1.0],
                ['name' => 'Self-Control', 'code' => 'SC', 'description' => 'Shows appropriate self-control', 'weight' => 1.0],
                ['name' => 'Initiative', 'code' => 'INIT', 'description' => 'Shows initiative in learning', 'weight' => 1.0],
                ['name' => 'Self-Confidence', 'code' => 'CONF', 'description' => 'Displays self-confidence', 'weight' => 1.0],
                ['name' => 'Resilience', 'code' => 'RES', 'description' => 'Bounces back from setbacks', 'weight' => 1.0],
                ['name' => 'Goal Setting', 'code' => 'GS', 'description' => 'Sets and works towards goals', 'weight' => 1.0],
                ['name' => 'Personal Hygiene', 'code' => 'PH', 'description' => 'Maintains good personal hygiene', 'weight' => 1.0],
                ['name' => 'Integrity', 'code' => 'INT', 'description' => 'Demonstrates honesty and ethical behavior', 'weight' => 1.0]
            ],
            'Work Habits' => [
                ['name' => 'Time Management', 'code' => 'TM', 'description' => 'Uses time effectively', 'weight' => 1.0],
                ['name' => 'Task Completion', 'code' => 'TC', 'description' => 'Completes tasks on time', 'weight' => 1.0],
                ['name' => 'Effort', 'code' => 'EFF', 'description' => 'Shows consistent effort', 'weight' => 1.0],
                ['name' => 'Independence', 'code' => 'IND', 'description' => 'Works independently', 'weight' => 1.0],
                ['name' => 'Following Instructions', 'code' => 'FI', 'description' => 'Follows directions accurately', 'weight' => 1.0],
                ['name' => 'Materials Management', 'code' => 'MM', 'description' => 'Manages learning materials well', 'weight' => 1.0],
                ['name' => 'Perseverance', 'code' => 'PER', 'description' => 'Persists in challenging tasks', 'weight' => 1.0],
                ['name' => 'Work Quality', 'code' => 'WQ', 'description' => 'Maintains high quality of work', 'weight' => 1.0]
            ]
        ];

        // Create traits with proper ordering
        $order = 0;
        foreach ($traits as $category => $categoryTraits) {
            foreach ($categoryTraits as $trait) {
                BehavioralTrait::create([
                    'school_id' => $school->id,
                    'name' => $trait['name'],
                    'code' => $trait['code'],
                    'description' => $trait['description'],
                    'category' => $category,
                    'weight' => $trait['weight'],
                    'display_order' => $order++,
                    'is_default' => in_array($trait['code'], $defaultTraitCodes)
                ]);
            }
        }
    }

    protected function setupDefaultPaymentTypes(School $school): void
    {
        // Define default payment types matching the model structure
        $paymentTypes = [
            // Tuition/School Fees for different levels
            [
                'name' => PaymentType::TUITION_PREFIX . ' (Nursery)',
                'category' => PaymentType::CATEGORY_SERVICE,
                'amount' => 75000,
                'active' => true,
                'is_tuition' => true,
                'class_level' => 'nursery',
                'installment_allowed' => true,
                'min_installment_amount' => 25000, // One term amount
                'has_due_date' => true,
                'description' => 'Nursery school tuition fees - can be paid termly or full session',
            ],
            [
                'name' => PaymentType::TUITION_PREFIX . ' (Primary)',
                'category' => PaymentType::CATEGORY_SERVICE,
                'amount' => 90000,
                'active' => true,
                'is_tuition' => true,
                'class_level' => 'primary',
                'installment_allowed' => true,
                'min_installment_amount' => 30000, // One term amount
                'has_due_date' => true,
                'description' => 'Primary school tuition fees - can be paid termly or full session',
            ],
            [
                'name' => PaymentType::TUITION_PREFIX . ' (Secondary)',
                'category' => PaymentType::CATEGORY_SERVICE,
                'amount' => 120000,
                'active' => true,
                'is_tuition' => true,
                'class_level' => 'secondary',
                'installment_allowed' => true,
                'min_installment_amount' => 40000, // One term amount
                'has_due_date' => true,
                'description' => 'Secondary school tuition fees - can be paid termly or full session',
            ],

            // Physical Items
            [
                'name' => 'School Uniform (Complete Set)',
                'category' => PaymentType::CATEGORY_PHYSICAL,
                'amount' => 15000,
                'active' => true,
                'is_tuition' => false,
                'installment_allowed' => false,
                'has_due_date' => false,
                'description' => 'Complete set of school uniform including sports wear',
            ],
            [
                'name' => 'PE Kit',
                'category' => PaymentType::CATEGORY_PHYSICAL,
                'amount' => 12000,
                'active' => true,
                'is_tuition' => false,
                'installment_allowed' => false,
                'has_due_date' => false,
                'description' => 'Physical Education kit for sports and athletics',
            ]
        ];
    
        try {
            foreach ($paymentTypes as $type) {
                // Create payment type
                $paymentType = PaymentType::create([
                    'school_id' => $school->id,
                    ...$type
                ]);

                // If it's a physical item, create inventory
                if ($paymentType->requiresInventory()) {
                    $paymentType->inventory()->create([
                        'school_id' => $school->id,
                        'name' => $type['name'],
                        'quantity' => 30, // Initial stock
                        'unit_price' => 10000,
                        'selling_price' => $type['amount'],
                        'is_active' => true,
                        'reorder_level' => 10, // Default reorder point
                        'description' => $type['description'],
                    ]);
                }
            }

            Log::info('Default payment types created successfully', [
                'school_id' => $school->id,
                'types_count' => count($paymentTypes)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create default payment types', [
                'school_id' => $school->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // In SchoolSetupService.php

    protected function setupDefaultPaymentPlans(School $school): void
    {
        // First get the tuition payment types to reference
        $tuitionTypes = [
            'nursery' => PaymentType::where('school_id', $school->id)
                ->where('class_level', 'nursery')
                ->where('is_tuition', true)
                ->first()?->id,
            'primary' => PaymentType::where('school_id', $school->id)
                ->where('class_level', 'primary')
                ->where('is_tuition', true)
                ->first()?->id,
            'secondary' => PaymentType::where('school_id', $school->id)
                ->where('class_level', 'secondary')
                ->where('is_tuition', true)
                ->first()?->id,
        ];

        // Define default payment plans matching model structure
        $paymentPlans = [
            // Nursery Level Plan
            [
                'school_id' => $school->id,
                'payment_type_id' => $tuitionTypes['nursery'],
                'name' => 'Nursery School Fees',
                'class_level' => 'nursery',
                'term_amount' => 25000.00,
                'session_amount' => 75000.00,
            ],

            // Primary Level Plan
            [
                'school_id' => $school->id,
                'payment_type_id' => $tuitionTypes['primary'],
                'name' => 'Primary School Fees',
                'class_level' => 'primary',
                'term_amount' => 30000.00,
                'session_amount' => 90000.00,
            ],

            // Secondary Level Plan
            [
                'school_id' => $school->id,
                'payment_type_id' => $tuitionTypes['secondary'],
                'name' => 'Secondary School Fees',
                'class_level' => 'secondary',
                'term_amount' => 40000.00,
                'session_amount' => 120000.00,
            ],
        ];

        try {
            // Create each payment plan
            foreach ($paymentPlans as $plan) {
                // Skip if payment type doesn't exist
                if (!$plan['payment_type_id']) {
                    Log::warning('Payment type not found for plan: ' . $plan['name'], [
                        'school_id' => $school->id,
                        'class_level' => $plan['class_level']
                    ]);
                    continue;
                }

                PaymentPlan::create($plan);
            }

            Log::info('Default payment plans created successfully', [
                'school_id' => $school->id,
                'plans_count' => count($paymentPlans)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create default payment plans', [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function createSettings(School $school): void
    {
        SchoolSettings::create([
            'school_id' => $school->id,
            'admission_settings' => [
                'format_type' => 'school_session', // Changed from 'basic' to 'school_session'
                'custom_format' => null,
                'prefix' => strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $school->name), 0, 3)),
                'length' => 4,
                'separator' => '/',
                'school_initials' => null,
                'initials_method' => 'first_letters',
                'session_format' => 'full_session', // Changed from 'short' to 'full_session'
                'number_start' => 1,
                'reset_sequence_yearly' => false,
                'reset_sequence_by_session' => true // Changed from false to true to reset numbers each session
            ],
            // ...existing code...
        ]);
    }
}

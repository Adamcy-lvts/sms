<?php

namespace App\Services;

use App\Models\Term;
use App\Models\School;
use App\Models\Subject;
use App\Models\Template;
use App\Models\ClassRoom;
use App\Models\Designation;
use App\Models\PaymentType;
use Illuminate\Support\Str;
use App\Models\GradingScale;
use App\Models\PaymentMethod;
use App\Models\AssessmentType;
use App\Models\SchoolSettings;
use App\Models\AcademicSession;
use App\Models\TemplateVariable;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolCalendarEvent;
use Illuminate\Support\Facades\Log;

class SchoolSetupService
{
    public function setup(School $school, array $data): void
    {
        try {
            // Create academic session and terms
            $this->setupAcademicPeriod($school);

            // Create school calendar events (holidays and breaks)
            $this->setupSchoolCalendar($school);

            // Create grading scales
            $this->createGradingScales($school);

            // Create designations  
            $this->createDesignations($school);

            // Set up payment methods
            $this->setupPaymentMethods($school);

            // Set up report templates
            $this->setupReportTemplates($school);

            // Create default templates and variables
            $this->createDefaultAdmissionTemplate($school);

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
        } catch (\Exception $e) {
            Log::error('School setup error: ' . $e->getMessage());
            throw $e;
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
                'name' => 'full_name',
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
                'name' => 'principal_name',
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
                            "attrs" => ["id" => "full_name"],
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
}

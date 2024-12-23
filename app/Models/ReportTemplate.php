<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ReportTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'name',
        'slug',
        'description',
        'header_config',
        'student_info_config',
        'grade_table_config',
        'comments_config', // Add this line
        'activities_config',
        'rtl_config',
        'print_config',
        'style_config',
        'is_default',
        'is_active'
    ];

    protected $casts = [
        'header_config' => 'array',
        'student_info_config' => 'array',
        'grade_table_config' => 'array',
        'comments_config' => 'array', // Add this line
        'activities_config' => 'array',
        'style_config' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'rtl_config' => 'array',
        'print_config' => 'array' // Add this
    ];

    public function getDefaultLogo(): string
    {
        return asset('img/school-logo.png');
    }

    public function getHeaderConfig(): array
    {
        $defaultConfig = [
            'show_logo' => true,
            'logo_height' => '100px',
            'logo_position' => 'center',
            'logo_url' => $this->getDefaultLogo(), // Add default logo URL
            'show_school_name' => true,
            'show_school_address' => true,
            'show_school_contact' => true,  // Add this line
            'show_report_title' => true,
            'report_title' => 'STUDENT REPORT CARD',
            'academic_info' => [
                'show_session' => true,
                'show_term' => true,
                'format' => [
                    'prefix' => '',
                    'separator' => ' - ',
                    'suffix' => ''
                ],
                'styles' => [
                    'size' => 'text-base',
                    'weight' => 'font-normal',
                    'color' => 'text-gray-600'
                ]
            ],
            'session_info_text' => '{session} Academic Session - {term}', // Default format
            'custom_styles' => [
                'header_background' => '',
                'text_color' => '',
                'title_size' => 'text-2xl',
                'school_name_size' => 'text-2xl',
                'session_info_size' => 'text-base',
                'contact_info_size' => 'text-sm'  // Add this line
            ]
        ];

        return array_merge(
            $defaultConfig,
            $this->header_config ?? []
        );
    }

    // Auto-generate slug from name
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            if (empty($template->slug)) {
                $template->slug = Str::slug($template->name);
            }
        });

        // Ensure only one default template per school
        static::saving(function ($template) {
            if ($template->is_default) {
                static::where('school_id', $template->school_id)
                    ->where('id', '!=', $template->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function sections()
    {
        return $this->hasMany(ReportSection::class)->orderBy('order');
    }

    public function assessmentColumns()
    {
        return $this->hasMany(ReportAssessmentColumn::class)->orderBy('order');
    }


    public function gradingScales()
    {
        return $this->hasMany(ReportGradingScale::class);
    }

    public function commentSections()
    {
        return $this->hasMany(ReportCommentSection::class)->orderBy('order');
    }

    protected function getDefaultStudentInfoConfig(): array
    {
        return [
            'sections' => [
                [
                    'title' => 'Student Information',
                    'key' => 'basic_info',
                    'enabled' => true,
                    'layout' => 'table',
                    'fields' => [
                        [
                            'label' => 'Student Name',
                            'admission_column' => 'full_name',
                            'key' => 'full_name',
                            'enabled' => true
                        ],
                        [
                            'label' => 'Admission Number',
                            'admission_column' => 'admission_number',
                            'key' => 'admission_number',
                            'enabled' => true
                        ],
                        [
                            'label' => 'Class',
                            'admission_column' => 'class',
                            'key' => 'class',
                            'enabled' => true
                        ],
                        [
                            'label' => 'Gender',
                            'admission_column' => 'gender',
                            'key' => 'gender',
                            'enabled' => true
                        ],
                        // [
                        //     'label' => 'Date of Birth',
                        //     'admission_column' => 'date_of_birth',
                        //     'key' => 'date_of_birth',
                        //     'enabled' => true
                        // ]
                    ]
                ]
            ]
        ];
    }

    public function getStudentInfoConfig(): array
    {
        $defaultConfig = $this->getDefaultStudentInfoConfig();
        $customConfig = $this->student_info_config ?? [];

        return $this->deepMerge($defaultConfig, $customConfig);
    }

    /**
     * Recursively merge arrays while preserving numeric keys
     */
    protected function deepMerge(array $default, array $custom): array
    {
        $merged = $default;

        foreach ($custom as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                // Recursively merge if both values are arrays
                $merged[$key] = $this->deepMerge($merged[$key], $value);
            } else {
                // Otherwise replace the value
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    // Add to ReportTemplate model
    public function getDefaultGradeTableConfig(): array
    {
        return [
            'enabled' => true,
            'title' => 'Academic Performance',
            'show_title' => true,
            'layout' => [
                'spacing' => 'p-4',
                'margin' => 'mb-6',
                'border' => 'border',
                'rounded' => 'rounded-lg',
                'shadow' => 'shadow-sm',
                'background' => 'bg-white',
            ],
            'header' => [
                'enabled' => true,
                'background' => 'bg-gray-50',
                'text_color' => 'text-gray-700',
                'font_weight' => 'font-semibold',
                'alignment' => 'text-left',
                'padding' => 'p-2',
                'border' => 'border-b'
            ],
            'columns' => [
                'subject' => [
                    'name' => 'Subject',
                    'width' => 'w-48',
                    'align' => 'text-left',
                    'enabled' => true,
                ],
                'assessment_columns' => [],
                'total' => [
                    'name' => 'Total',
                    'width' => 'w-20',
                    'align' => 'text-center',
                    'enabled' => true,
                ],
                'grade' => [
                    'name' => 'Grade',
                    'width' => 'w-20',
                    'align' => 'text-center',
                    'enabled' => true,
                ],
                'remark' => [
                    'name' => 'Remark',
                    'width' => 'w-32',
                    'align' => 'text-left',
                    'enabled' => true,
                ]
            ],
            'rows' => [
                'striped' => true,
                'hover' => true,
                'padding' => 'p-2',
                'border' => 'border-t',
                'text_color' => 'text-gray-700'
            ],
            'totals_footer' => [
                'enabled' => true,
                'background' => 'bg-gray-50',
                'font_weight' => 'font-semibold',
                'border' => 'border-t-2'
            ],
            'grading_scale' => [
                'enabled' => true,
                'title' => 'Grading Scale',
                'layout' => 'grid', // grid or table
                'columns' => 5
            ]
        ];
    }

    public function getGradeTableConfig(): array
    {
        return array_merge(
            $this->getDefaultGradeTableConfig(),
            $this->grade_table_config ?? []
        );
    }

    protected function getDefaultCommentsConfig(): array
    {
        return [
            'enabled' => true,
            'title' => 'Comments & Signatures',
            'sections' => [
                'class_teacher' => [
                    'title' => "Class Teacher's Comment",
                    'enabled' => false,
                    'signature' => [
                        'show_digital' => true,
                        'show_manual' => true,
                        'show_date' => true,
                    ],
                    'style' => [
                        'background' => 'light',
                        'border' => 'rounded'
                    ]
                ],
                'principal' => [
                    'title' => "Principal's Comment",
                    'enabled' => false,
                    'signature' => [
                        'show_digital' => true,
                        'show_manual' => true,
                        'show_date' => true,
                    ],
                    'style' => [
                        'background' => 'light',
                        'border' => 'rounded'
                    ]
                ]
            ]
        ];
    }

    public function getCommentsConfig(): array
    {
        return array_replace_recursive(
            $this->getDefaultCommentsConfig(),
            $this->comments_config ?? []
        );
    }

    protected function getDefaultActivitiesConfig(): array
    {
        return [
            'enabled' => true,
            'layout' => 'side-by-side',
            'spacing' => 'normal',
            'table_style' => [
                'font_size' => '0.875rem',
                'cell_padding' => '0.75rem',
                'row_height' => '2.5rem'
            ],
            'sections' => [
                [
                    'title' => 'Sports & Athletics',
                    'enabled' => true,
                    'type' => 'rating', // Ensure type is set
                    'columns' => [
                        ['key' => 'name', 'label' => 'Activity'],
                        ['key' => 'rating', 'label' => 'Rating'],
                        ['key' => 'performance', 'label' => 'Performance']
                    ],
                    'fields' => [],
                ],

                [
                    'title' => 'Grade Scale',
                    'enabled' => true,
                    'type' => 'grade_scale',
                    'use_grading_scale_model' => true,
                    'columns' => [
                        ['key' => 'grade', 'label' => 'Grade'],
                        ['key' => 'range', 'label' => 'Range'],
                        ['key' => 'remark', 'label' => 'Remark']
                    ],
                ]
            ]
        ];
    }

    public function getActivitiesConfig(): array
    {
        $savedConfig = $this->activities_config ?? [];
        $defaultConfig = $this->getDefaultActivitiesConfig();

        // Merge base config
        $mergedConfig = [
            'enabled' => $savedConfig['enabled'] ?? $defaultConfig['enabled'],
            'layout' => $savedConfig['layout'] ?? $defaultConfig['layout'],
            'spacing' => $savedConfig['spacing'] ?? $defaultConfig['spacing'],
            'table_style' => array_merge(
                $defaultConfig['table_style'],
                $savedConfig['table_style'] ?? []
            ),
        ];

        // Handle sections with careful type checking
        $mergedConfig['sections'] = [];
        if (!empty($savedConfig['sections'])) {
            foreach ($savedConfig['sections'] as $section) {
                // Ensure required properties exist
                $section['enabled'] = $section['enabled'] ?? true;
                $section['type'] = $section['type'] ?? 'rating'; // Set default type

                // Ensure columns exist
                if (!isset($section['columns'])) {
                    $section['columns'] = $section['type'] === 'grade_scale'
                        ? [
                            ['key' => 'grade', 'label' => 'Grade'],
                            ['key' => 'range', 'label' => 'Range'],
                            ['key' => 'remark', 'label' => 'Remark']
                        ]
                        : [
                            ['key' => 'name', 'label' => 'Activity'],
                            ['key' => 'rating', 'label' => 'Rating'],
                            ['key' => 'performance', 'label' => 'Performance']
                        ];
                }

                // Handle fields if not grade scale
                if ($section['type'] !== 'grade_scale') {
                    $section['fields'] = array_map(function ($field) {
                        return [
                            'name' => $field['name'] ?? '',
                            'enabled' => $field['enabled'] ?? true,
                            'type' => 'rating',
                            'value' => $field['value'] ?? ['rating' => null, 'performance' => null],
                            'style' => array_merge([
                                'text_color' => 'warning',
                                'alignment' => 'center'
                            ], $field['style'] ?? [])
                        ];
                    }, $section['fields'] ?? []);
                }

                $mergedConfig['sections'][] = $section;
            }
        } else {
            $mergedConfig['sections'] = $defaultConfig['sections'];
        }

        return $mergedConfig;
    }

    public function getDefaultRtlConfig(): array
    {
        return [
            'enable_arabic' => false,
            'arabic_font' => 'Noto Naskh Arabic',
            'subjects' => [
                'show_arabic_names' => false,
                'display_style' => 'brackets',
                'arabic_column_position' => 'after',
                'arabic_text_size' => '0.875rem',
                'arabic_text_color' => '#374151',
                'bold_arabic' => false,
                'separator' => 'brackets'
            ],

            'header' => [
                'show_arabic_name' => false,
                'arabic_name_position' => 'above', // above, below, or opposite
                'arabic_text_size' => 'text-lg'
            ],
        ];
    }

    public function getRtlConfig(): array
    {
        return array_merge(
            $this->getDefaultRtlConfig(),
            $this->rtl_config ?? []
        );
    }

    protected function getDefaultPrintConfig(): array
    {
        return [
            'paper_size' => 'A4',
            'margins' => [
                'top' => 10,
                'right' => 10,
                'bottom' => 10,
                'left' => 10
            ],
            'header' => [
                'logo_height' => 80,
                'school_name' => [
                    'font_size' => 14,
                    'margin_bottom' => 4
                ],
                'address' => [
                    'font_size' => 11,
                    'margin_bottom' => 4
                ],
                'contact_info' => [
                    'font_size' => 10,
                    'margin_bottom' => 4
                ],
                'report_title' => [
                    'font_size' => 12,
                    'margin_top' => 8,
                    'margin_bottom' => 8
                ],
                'academic_info' => [
                    'font_size' => 11,
                    'margin_bottom' => 8
                ],
                'spacing' => 8
            ],
            'student_info' => [
                'title' => [
                    'font_size' => 12,
                    'margin_bottom' => 8
                ],
                'labels' => [
                    'font_size' => 11,
                    'font_weight' => 600
                ],
                'values' => [
                    'font_size' => 11
                ],
                'row_height' => 24,
                'spacing' => 6
            ],
            'grades_table' => [
                'header' => [
                    'font_size' => 11,
                    'font_weight' => 600,
                    'padding' => 6
                ],
                'cells' => [
                    'font_size' => 10,
                    'padding' => 6,
                    'row_height' => 24
                ],
                'spacing' => 8
            ],
            'activities' => [
                'section_title' => [
                    'font_size' => 12,
                    'margin_bottom' => 6
                ],
                'content' => [
                    'font_size' => 10,
                    'row_height' => 20
                ],
                'rating_size' => 16,
                'spacing' => 8
            ],
            'comments' => [
                'title' => [
                    'font_size' => 11,
                    'margin_bottom' => 6
                ],
                'content' => [
                    'font_size' => 10,
                    'margin_bottom' => 8
                ],
                'signature' => [
                    'font_size' => 10,
                    'margin_top' => 8
                ],
                'spacing' => 10
            ]
        ];
    }

    public function getPrintConfig(): array
    {
        return array_replace_recursive(
            $this->getDefaultPrintConfig(),
            $this->print_config ?? []
        );
    }
}

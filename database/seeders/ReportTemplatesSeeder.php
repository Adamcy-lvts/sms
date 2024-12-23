<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ReportTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('report_templates')->insert([
            [
                'id' => 1,
                'school_id' => 2,
                'name' => 'Standard Term Report',
                'slug' => 'standard-term-report',
                'description' => 'Default end of term report template',
                'header_config' => json_encode([
                    'styles' => [
                        'padding' => 'p-8',
                        'text_color' => '#1421cc',
                        'margin_bottom' => null,
                        'background_color' => '#260707'
                    ],
                    'spacing' => ['gap' => null],
                    'show_logo' => true,
                    'typography' => [
                        'address' => [
                            'font_size' => '0.875rem',
                            'line_height' => '1.4',
                            'margin_bottom' => '0.25rem'
                        ],
                        'contact' => [
                            'gap' => '0.5rem',
                            'font_size' => '1rem',
                            'line_height' => '1.6',
                            'margin_bottom' => '0.75rem'
                        ],
                        'school_name' => [
                            'weight' => '800',
                            'font_size' => '1.5rem',
                            'text_case' => 'uppercase',
                            'line_height' => '1.2',
                            'margin_bottom' => '0.5rem'
                        ],
                        'report_title' => [
                            'margin' => '0.5rem',
                            'font_size' => '1rem',
                            'line_height' => '1'
                        ]
                    ],
                    'logo_height' => '150px',
                    'logo_margin' => 'tight',
                    'contact_info' => ['layout' => 'inline'],
                    'report_title' => ['report_title' => 'REPORT CARD'],
                    'academic_info' => [
                        'format' => [
                            'prefix' => null,
                            'suffix' => null,
                            'separator' => null
                        ],
                        'styles' => [
                            'size' => 'text-sm',
                            'color' => 'text-primary-600',
                            'weight' => 'font-semibold'
                        ],
                        'show_term' => true,
                        'show_session' => true
                    ],
                    'logo_position' => 'center',
                    'show_school_name' => true,
                    'show_report_title' => true,
                    'show_school_email' => false,
                    'show_school_phone' => false,
                    'show_school_address' => true,
                    'show_school_contact' => true
                ]),
                'student_info_config' => json_encode([
                    'layout' => 'single',
                    'padding' => [
                        'row' => '0.25rem',
                        'grid' => '0.5rem',
                        'container' => '1rem'
                    ],
                    'sections' => [
                        [
                            'key' => 'basic_info',
                            'order' => null,
                            'title' => 'Student Information',
                            'width' => 'full',
                            'fields' => [
                                [
                                    'key' => 'full_name',
                                    'label' => 'Student Name',
                                    'order' => null,
                                    'width' => 'full',
                                    'enabled' => true,
                                    'field_type' => 'student',
                                    'admission_column' => 'full_name'
                                ],
                                [
                                    'key' => 'class_room_id',
                                    'label' => 'Class',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'student',
                                    'admission_column' => 'class_room_id'
                                ],
                                [
                                    'key' => 'admission_number',
                                    'label' => 'Admission Number',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'admission',
                                    'admission_column' => 'admission_number'
                                ],
                                [
                                    'key' => 'date_of_birth',
                                    'label' => 'Date of Birth',
                                    'order' => 5,
                                    'width' => 'full',
                                    'enabled' => true,
                                    'field_type' => 'admission',
                                    'admission_column' => 'date_of_birth'
                                ]
                            ],
                            'layout' => 'grid',
                            'columns' => '2',
                            'enabled' => true,
                            'spacing' => null,
                            'background' => 'hover',
                            'label_size' => null,
                            'title_size' => 'text-sm',
                            'value_size' => null,
                            'label_color' => null,
                            'value_color' => null,
                            'use_custom_styles' => true,
                            'student_info_config' => [
                                'default_styles' => [
                                    'border_style' => 'none',
                                    'background_style' => 'hover'
                                ]
                            ]
                        ],
                        [
                            'key' => 'term_summary',
                            'order' => null,
                            'title' => 'Term Summary',
                            'width' => 'full',
                            'fields' => [
                                [
                                    'key' => 'total_score',
                                    'label' => 'Total Score',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'total_score'
                                ],
                                [
                                    'key' => 'average',
                                    'label' => 'Average Score',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'average'
                                ],
                                [
                                    'key' => 'position',
                                    'label' => 'Position',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'position'
                                ],
                                [
                                    'key' => 'class_size',
                                    'label' => 'Number in Class',
                                    'order' => null,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'class_size'
                                ],
                                [
                                    'key' => 'attendance_percentage',
                                    'label' => 'Attendance %',
                                    'order' => 5,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'attendance_percentage'
                                ],
                                [
                                    'key' => 'school_days',
                                    'label' => 'School Days',
                                    'order' => 6,
                                    'width' => 'half',
                                    'enabled' => true,
                                    'field_type' => 'term_summary',
                                    'admission_column' => 'school_days'
                                ]
                            ],
                            'layout' => 'grid',
                            'columns' => 2,
                            'enabled' => true,
                            'spacing' => null,
                            'label_size' => null,
                            'title_size' => null,
                            'value_size' => null,
                            'label_color' => null,
                            'value_color' => null,
                            'use_custom_styles' => false,
                            'student_info_config' => [
                                'default_styles' => [
                                    'border_style' => null,
                                    'background_style' => null
                                ]
                            ]
                        ]
                    ],
                    'default_styles' => [
                        'spacing' => 'py-1',
                        'label_size' => 'text-xs',
                        'title_size' => 'text-base',
                        'value_size' => 'text-xs',
                        'label_color' => '#bf692d',
                        'value_color' => null,
                        'border_color' => '#e5e7eb',
                        'border_style' => 'full',
                        'stripe_color' => '#f9fafb',
                        'background_style' => 'striped'
                    ],
                    'table_arrangement' => 'side-by-side'
                ]),
                'grade_table_config' => json_encode([
                    'rows' => [
                        'font_size' => '0.75rem',
                        'font_weight' => null,
                        'stripe_color' => null
                    ],
                    'title' => null,
                    'border' => [
                        'color' => '#a3147b',
                        'style' => 'premium',
                        'width' => '2px',
                        'radius' => '0.5rem'
                    ],
                    'colors' => [
                        'fail' => '#e01f1f',
                        'good' => '#79ade8',
                        'poor' => '#bd5d1d',
                        'excellent' => '#15803d',
                        'very_good' => '#2566a8'
                    ],
                    'footer' => [
                        'enabled' => false
                    ],
                    'header' => [
                        'padding' => '0.5rem',
                        'font_size' => null,
                        'background' => null,
                        'text_color' => '#6e4949',
                        'font_weight' => 'font-semibold'
                    ],
                    'layout' => [
                        'margin' => '1.5rem',
                        'rounded' => '0.5rem'
                    ],
                    'columns' => [
                        'grade' => [
                            'enabled' => true
                        ],
                        'total' => [
                            'enabled' => true
                        ],
                        'remark' => [
                            'enabled' => true
                        ],
                        'subject' => [
                            'show' => true
                        ]
                    ],
                    'enabled' => true,
                    'spacing' => null,
                    'font_size' => '1rem',
                    'background' => null,
                    'show_grade' => true,
                    'show_title' => false,
                    'show_total' => true,
                    'font_family' => null,
                    'line_height' => null,
                    'show_remark' => true,
                    'cell_padding' => null,
                    'grade_column' => [
                        'width' => null
                    ],
                    'total_column' => [
                        'width' => null
                    ],
                    'remark_column' => [
                        'width' => null
                    ],
                    'color_settings' => [
                        'apply_to_grade' => true,
                        'apply_to_total' => true,
                        'apply_to_remark' => true,
                        'apply_to_subject' => true
                    ],
                    'subject_column' => [
                        'align' => 'text-left',
                        'title' => 'Subject',
                        'width' => 'w-32'
                    ],
                    'assessment_columns' => [
                        [
                            'key' => 'ca1',
                            'name' => 'CA 1',
                            'width' => 'w-20',
                            'weight' => '10',
                            'max_score' => '10',
                            'show_max_score' => true
                        ],
                        [
                            'key' => 'ca2',
                            'name' => 'CA 2',
                            'width' => 'w-20',
                            'weight' => '10',
                            'max_score' => '10',
                            'show_max_score' => true
                        ],
                        [
                            'key' => 'ca3',
                            'name' => 'C3',
                            'width' => 'w-20',
                            'weight' => '10',
                            'max_score' => '10',
                            'show_max_score' => true
                        ],
                        [
                            'key' => 'exam',
                            'name' => 'Exam',
                            'width' => 'w-20',
                            'weight' => '70',
                            'max_score' => '70',
                            'show_max_score' => true
                        ]
                    ]
                ]),
                'activities_config' => json_encode([
                    'layout' => 'side-by-side',
                    'enabled' => true,
                    'spacing' => 'normal',
                    'sections' => [
                        [
                            'type' => 'rating',
                            'style' => [
                                'shadow' => true,
                                'background' => 'light'
                            ],
                            'title' => 'Activities',
                            'fields' => [
                                [
                                    'name' => 'Football',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Basketball',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Drama Club',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 3,
                                        'performance' => 'Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Science Club',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 3,
                                        'performance' => 'Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Debate Club',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Chess Club',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 3,
                                        'performance' => 'Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Student Council',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Community Service',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ]
                            ],
                            'columns' => [
                                [
                                    'key' => 'name',
                                    'label' => 'Activity/Trait'
                                ],
                                [
                                    'key' => 'rating',
                                    'label' => 'Rating'
                                ],
                                [
                                    'key' => 'performance',
                                    'label' => 'Performance'
                                ]
                            ],
                            'enabled' => true
                        ],
                        [
                            'type' => 'rating',
                            'style' => [
                                'shadow' => true,
                                'background' => 'light'
                            ],
                            'title' => 'Behavioral Traits',
                            'fields' => [
                                [
                                    'name' => 'Attentiveness',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Class Participation',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Respect',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Communication',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 5,
                                        'performance' => 'Excellent'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Team Work',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 5,
                                        'performance' => 'Excellent'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Punctuality',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 3,
                                        'performance' => 'Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Neatness',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 5,
                                        'performance' => 'Excellent'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Self-Control',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 4,
                                        'performance' => 'Very Good'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Integrity',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 5,
                                        'performance' => 'Excellent'
                                    ],
                                    'enabled' => true
                                ],
                                [
                                    'name' => 'Effort',
                                    'type' => 'rating',
                                    'style' => [
                                        'alignment' => 'center',
                                        'text_color' => 'warning'
                                    ],
                                    'value' => [
                                        'rating' => 5,
                                        'performance' => 'Excellent'
                                    ],
                                    'enabled' => true
                                ]
                            ],
                            'columns' => [
                                [
                                    'key' => 'name',
                                    'label' => 'Activity/Trait'
                                ],
                                [
                                    'key' => 'rating',
                                    'label' => 'Rating'
                                ],
                                [
                                    'key' => 'performance',
                                    'label' => 'Performance'
                                ]
                            ],
                            'enabled' => true
                        ]
                    ],
                    'table_style' => [
                        'font_size' => '0.875rem',
                        'row_height' => '2.5rem',
                        'cell_padding' => '0.75rem'
                    ]
                ]),
                'comments_config' => json_encode([
                    'layout' => 'side-by-side',
                    'enabled' => true,
                    'spacing' => 'normal',
                    'sections' => [
                        [
                            'type' => 'predefined',
                            'style' => [
                                'border' => 'rounded',
                                'font_size' => '0.75rem',
                                'background' => 'light'
                            ],
                            'title' => "Head Teacher's Comment",
                            'enabled' => true,
                            'required' => true,
                            'show_signatures' => true,
                            'signature_fields' => [
                                'width' => null,
                                'alignment' => 'left',
                                'show_date' => true,
                                'show_name' => true,
                                'show_manual' => false,
                                'show_digital' => true
                            ]
                        ],
                        [
                            'type' => 'predefined',
                            'style' => [
                                'border' => 'rounded',
                                'font_size' => '0.75rem',
                                'background' => 'light'
                            ],
                            'title' => "Principal's Comment",
                            'enabled' => true,
                            'required' => true,
                            'show_signatures' => true,
                            'signature_fields' => [
                                'width' => null,
                                'alignment' => 'left',
                                'show_date' => true,
                                'show_name' => true,
                                'show_manual' => false,
                                'show_digital' => true
                            ]
                        ]
                    ]
                ]),
                'print_config' => json_encode([
                    'header' => [
                        'address' => [
                            'font_size' => '11'
                        ],
                        'spacing' => '6',
                        'logo_height' => '80',
                        'school_name' => [
                            'font_size' => '12'
                        ],
                        'contact_info' => [
                            'font_size' => '10'
                        ],
                        'report_title' => [
                            'font_size' => '10'
                        ]
                    ],
                    'margins' => [
                        'top' => null,
                        'left' => null,
                        'right' => null,
                        'bottom' => null
                    ],
                    'comments' => [
                        'title' => [
                            'font_size' => '10'
                        ],
                        'content' => [
                            'font_size' => '9'
                        ],
                        'signature' => [
                            'font_size' => '8'
                        ],
                        'line_height' => '1',
                        'section_gap' => '4',
                        'title_margin' => '4',
                        'section_spacing' => '4',
                        'signature_height' => '24',
                        'container_padding' => '8'
                    ],
                    'activities' => [
                        'content' => [
                            'font_size' => '8'
                        ],
                        'table_gap' => '8',
                        'row_height' => '10',
                        'line_height' => '0.8',
                        'rating_size' => '12',
                        'grading_scale' => [
                            'font_size' => '8',
                            'cell_padding' => '6'
                        ],
                        'section_title' => [
                            'font_size' => '10'
                        ],
                        'rating_row_height' => '10',
                        'table_row_spacing' => '4',
                        'table_cell_padding' => '4',
                        'table_margin_bottom' => '8'
                    ],
                    'paper_size' => null,
                    'grades_table' => [
                        'cells' => [
                            'padding' => '6',
                            'font_size' => '9'
                        ],
                        'header' => [
                            'font_size' => '11'
                        ],
                        'line_height' => '1',
                        'margin_bottom' => '8',
                        'container_padding' => '12'
                    ],
                    'student_info' => [
                        'title' => [
                            'font_size' => '10'
                        ],
                        'labels' => [
                            'font_size' => '8'
                        ],
                        'values' => [
                            'font_size' => '8'
                        ],
                        'line_height' => '1',
                        'row_spacing' => '1',
                        'section_gap' => '2',
                        'title_margin' => '4',
                        'container_padding' => '4'
                    ]
                ]),
                'rtl_config' => json_encode([
                    'header' => [
                        'arabic_text_size' => 'text-base',
                        'show_arabic_name' => false,
                        'arabic_name_position' => 'above'
                    ],
                    'subjects' => [
                        'bold_arabic' => true,
                        'display_style' => 'brackets',
                        'arabic_text_size' => 'text-lg',
                        'show_arabic_names' => true
                    ],
                    'arabic_font' => 'Noto Naskh Arabic',
                    'enable_arabic' => true,
                    'text_direction' => 'rtl'
                ]),
                'is_default' => 1,
                'is_active' => 1,
                'created_at' => '2024-11-16 18:31:06',
                'updated_at' => '2024-12-19 14:05:02'
            ]
        ]);

        DB::table('report_templates')->insert([
            'id' => 2,
            'school_id' => 2,
            'name' => 'Standard Term Report Card',
            'slug' => 'standard-term-report-card',
            'description' => null,
            'header_config' => json_encode([
                'styles' => [
                    'padding' => 'p-6',
                    'text_color' => null,
                    'margin_bottom' => 'mb-6',
                    'background_color' => null
                ],
                'show_logo' => true,
                'logo_height' => '100px',
                'logo_margin' => 'normal',
                'contact_info' => [
                    'layout' => 'inline',
                    'email_label' => 'Email',
                    'phone_label' => 'Tel',
                    'website_label' => 'Website'
                ],
                'report_title' => 'STUDENT REPORT CARD',
                'academic_info' => [
                    'format' => [
                        'prefix' => null,
                        'suffix' => null,
                        'separator' => null
                    ],
                    'styles' => [
                        'size' => 'text-base',
                        'color' => 'text-primary-600',
                        'weight' => 'font-medium'
                    ],
                    'show_term' => true,
                    'show_session' => true
                ],
                'custom_styles' => [
                    'contact_info_size' => 'text-sm'
                ],
                'logo_position' => 'center',
                'school_name_size' => 'text-2xl',
                'show_school_name' => true,
                'report_title_size' => 'text-xl',
                'show_report_title' => true,
                'show_school_email' => true,
                'show_school_phone' => true,
                'show_school_address' => true,
                'show_school_contact' => true,
                'show_school_website' => true
            ]),
            'student_info_config' => json_encode([
                'layout' => 'single',
                'padding' => [
                    'row' => '0.5rem',
                    'grid' => '0.5rem',
                    'container' => '0.5rem'
                ],
                'sections' => [
                    [
                        'key' => 'basic_info',
                        'order' => 1,
                        'title' => 'Student Information',
                        'width' => 'full',
                        'fields' => [
                            [
                                'key' => 'full_name',
                                'label' => 'Student Name',
                                'order' => 1,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => null,
                                'admission_column' => 'full_name'
                            ],
                            [
                                'key' => 'class_room_id',
                                'label' => 'Class',
                                'order' => 4,
                                'width' => 'full',
                                'enabled' => true,
                                'field_type' => 'student',
                                'admission_column' => 'class_room_id'
                            ],
                            [
                                'key' => 'admission_number',
                                'label' => 'Admission Number',
                                'order' => 5,
                                'width' => 'full',
                                'enabled' => true,
                                'field_type' => 'admission',
                                'admission_column' => 'admission_number'
                            ],
                            [
                                'key' => 'guardian_name',
                                'label' => 'Parent/Guardian Name',
                                'order' => 6,
                                'width' => 'half',
                                'enabled' => false,
                                'field_type' => 'admission',
                                'admission_column' => 'guardian_name'
                            ],
                            [
                                'key' => 'date_of_birth',
                                'label' => 'Date of Birth',
                                'order' => 5,
                                'width' => 'full',
                                'enabled' => false,
                                'field_type' => 'admission',
                                'admission_column' => 'date_of_birth'
                            ],
                            [
                                'key' => 'state_id',
                                'label' => 'State',
                                'order' => 6,
                                'width' => 'half',
                                'enabled' => false,
                                'field_type' => 'admission',
                                'admission_column' => 'state_id'
                            ],
                            [
                                'key' => 'lga_id',
                                'label' => 'LGA',
                                'order' => 7,
                                'width' => 'half',
                                'enabled' => false,
                                'field_type' => 'admission',
                                'admission_column' => 'lga_id'
                            ]
                        ],
                        'layout' => 'grid',
                        'columns' => '2',
                        'enabled' => true,
                        'spacing' => null,
                        'label_size' => null,
                        'title_size' => null,
                        'value_size' => null,
                        'label_color' => null,
                        'value_color' => null,
                        'use_custom_styles' => false,
                        'student_info_config' => [
                            'default_styles' => [
                                'border_style' => 'divider',
                                'background_style' => 'none'
                            ]
                        ]
                    ],
                    [
                        'key' => 'term_summary',
                        'order' => 2,
                        'title' => 'Term Summary',
                        'width' => 'full',
                        'fields' => [
                            [
                                'key' => 'total_score',
                                'label' => 'Total Score',
                                'order' => 1,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'total_score'
                            ],
                            [
                                'key' => 'average',
                                'label' => 'Average Score',
                                'order' => 2,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'average'
                            ],
                            [
                                'key' => 'position',
                                'label' => 'Position',
                                'order' => 3,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'position'
                            ],
                            [
                                'key' => 'class_size',
                                'label' => 'Number In Class',
                                'order' => 4,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'class_size'
                            ],
                            [
                                'key' => 'attendance_percentage',
                                'label' => 'Attendance',
                                'order' => 5,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'attendance_percentage'
                            ],
                            [
                                'key' => 'resumption_date',
                                'label' => 'Resumption Date',
                                'order' => 6,
                                'width' => 'half',
                                'enabled' => true,
                                'field_type' => 'term_summary',
                                'admission_column' => 'resumption_date'
                            ],
                            [
                                'key' => 'school_days',
                                'label' => 'School Days',
                                'order' => 7,
                                'width' => 'half',
                                'enabled' => false,
                                'field_type' => 'term_summary',
                                'admission_column' => 'school_days'
                            ]
                        ],
                        'layout' => 'grid',
                        'columns' => 2,
                        'enabled' => true,
                        'spacing' => null,
                        'label_size' => null,
                        'title_size' => null,
                        'value_size' => null,
                        'label_color' => null,
                        'value_color' => null,
                        'use_custom_styles' => false,
                        'student_info_config' => [
                            'default_styles' => [
                                'border_style' => 'divider',
                                'background_style' => 'none'
                            ]
                        ]
                    ]
                ],
                'default_styles' => [
                    'spacing' => 'py-1.5',
                    'label_size' => 'text-sm',
                    'title_size' => 'text-base',
                    'value_size' => 'text-sm',
                    'hover_color' => '#f3f4f6',
                    'label_color' => null,
                    'value_color' => null,
                    'border_color' => '#e5e7eb',
                    'border_style' => 'both',
                    'stripe_color' => '#f9fafb',
                    'background_style' => 'both'
                ],
                'table_arrangement' => 'side-by-side'
            ]),
            'grade_table_config' => json_encode([
                'rows' => [
                    'font_size' => '0.875rem',
                    'font_weight' => '600',
                    'stripe_color' => '#f9fafb'
                ],
                'title' => 'Academic Performance',
                'border' => [
                    'color' => '#e5e7eb',
                    'style' => 'single',
                    'width' => '1px',
                    'radius' => '0.375rem'
                ],
                'colors' => [
                    'fail' => '#dc2626',
                    'good' => '#0369a1',
                    'poor' => '#d97706',
                    'excellent' => '#15803d',
                    'very_good' => '#166534'
                ],
                'footer' => [
                    'enabled' => false
                ],
                'header' => [
                    'padding' => '0.5rem',
                    'font_size' => '0.875rem',
                    'background' => '#f3f4f6',
                    'text_color' => '#111827',
                    'font_weight' => 'font-semibold'
                ],
                'layout' => [
                    'margin' => '1.5rem',
                    'rounded' => '0.8rem'
                ],
                'columns' => [
                    'grade' => ['enabled' => true],
                    'total' => ['enabled' => true],
                    'remark' => ['enabled' => true],
                    'subject' => ['show' => true]
                ],
                'enabled' => true,
                'spacing' => 'normal',
                'font_size' => '0.875rem',
                'background' => null,
                'show_grade' => true,
                'show_title' => false,
                'show_total' => true,
                'font_family' => 'system-ui, -apple-system, sans-serif',
                'line_height' => '1.25',
                'show_remark' => true,
                'cell_padding' => '0.5rem',
                'grade_column' => ['width' => 'w-16'],
                'total_column' => ['width' => 'w-20'],
                'remark_column' => ['width' => 'w-32'],
                'color_settings' => [
                    'apply_to_grade' => true,
                    'apply_to_total' => true,
                    'apply_to_remark' => true,
                    'apply_to_subject' => true
                ],
                'subject_column' => [
                    'align' => 'text-left',
                    'title' => 'Subject',
                    'width' => 'w-48'
                ],
                'assessment_columns' => [
                    [
                        'key' => 'ca1',
                        'name' => 'CA1',
                        'width' => 'w-20',
                        'weight' => 0,
                        'max_score' => 100,
                        'show_max_score' => true
                    ],
                    [
                        'key' => 'ca2',
                        'name' => 'CA2',
                        'width' => 'w-20',
                        'weight' => 0,
                        'max_score' => 100,
                        'show_max_score' => true
                    ],
                    [
                        'key' => 'exam',
                        'name' => 'Exam',
                        'width' => 'w-20',
                        'weight' => 0,
                        'max_score' => 100,
                        'show_max_score' => true
                    ]
                ]
            ]),
            'activities_config' => json_encode([
                'layout' => 'side-by-side',
                'enabled' => true,
                'spacing' => 'normal',
                'sections' => [
                    [
                        'type' => 'rating',
                        'style' => [
                            'shadow' => true,
                            'background' => 'light'
                        ],
                        'title' => 'Activities',
                        'fields' => [
                            [
                                'name' => 'Football',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Basketball',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Drama Club',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 3,
                                    'performance' => 'Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Science Club',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 3,
                                    'performance' => 'Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Debate Club',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Chess Club',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 3,
                                    'performance' => 'Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Student Council',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Community Service',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ]
                        ],
                        'columns' => [
                            [
                                'key' => 'name',
                                'label' => 'Activity/Trait'
                            ],
                            [
                                'key' => 'rating',
                                'label' => 'Rating'
                            ],
                            [
                                'key' => 'performance',
                                'label' => 'Performance'
                            ]
                        ],
                        'enabled' => true
                    ],
                    [
                        'type' => 'rating',
                        'style' => [
                            'shadow' => true,
                            'background' => 'light'
                        ],
                        'title' => 'Behavioral Traits',
                        'fields' => [
                            [
                                'name' => 'Attentiveness',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Class Participation',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Respect',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Communication',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 5,
                                    'performance' => 'Excellent'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Team Work',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 5,
                                    'performance' => 'Excellent'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Punctuality',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 3,
                                    'performance' => 'Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Neatness',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 5,
                                    'performance' => 'Excellent'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Self-Control',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 4,
                                    'performance' => 'Very Good'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Integrity',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 5,
                                    'performance' => 'Excellent'
                                ],
                                'enabled' => true
                            ],
                            [
                                'name' => 'Effort',
                                'type' => 'rating',
                                'style' => [
                                    'alignment' => 'center',
                                    'text_color' => 'warning'
                                ],
                                'value' => [
                                    'rating' => 5,
                                    'performance' => 'Excellent'
                                ],
                                'enabled' => true
                            ]
                        ],
                        'columns' => [
                            [
                                'key' => 'name',
                                'label' => 'Activity/Trait'
                            ],
                            [
                                'key' => 'rating',
                                'label' => 'Rating'
                            ],
                            [
                                'key' => 'performance',
                                'label' => 'Performance'
                            ]
                        ],
                        'enabled' => true
                    ]
                ],
                'table_style' => [
                    'font_size' => '0.875rem',
                    'row_height' => '2.5rem',
                    'cell_padding' => '0.75rem'
                ]
            ]),

            'comments_config' => json_encode([
                'layout' => 'side-by-side',
                'enabled' => true,
                'spacing' => 'normal',
                'sections' => [
                    [
                        'type' => 'predefined',
                        'style' => [
                            'border' => 'rounded',
                            'font_size' => '0.75rem',
                            'background' => 'light'
                        ],
                        'title' => "Head Teacher's Comment",
                        'enabled' => true,
                        'required' => true,
                        'show_signatures' => true,
                        'signature_fields' => [
                            'width' => null,
                            'alignment' => 'left',
                            'show_date' => true,
                            'show_name' => true,
                            'show_manual' => false,
                            'show_digital' => true
                        ]
                    ],
                    [
                        'type' => 'predefined',
                        'style' => [
                            'border' => 'rounded',
                            'font_size' => '0.75rem',
                            'background' => 'light'
                        ],
                        'title' => "Principal's Comment",
                        'enabled' => true,
                        'required' => true,
                        'show_signatures' => true,
                        'signature_fields' => [
                            'width' => null,
                            'alignment' => 'left',
                            'show_date' => true,
                            'show_name' => true,
                            'show_manual' => false,
                            'show_digital' => true
                        ]
                    ]
                ]
            ]),

            'print_config' => json_encode([
                'header' => [
                    'address' => [
                        'font_size' => '13'
                    ],
                    'spacing' => '6',
                    'logo_height' => '80',
                    'school_name' => [
                        'font_size' => '16'
                    ],
                    'contact_info' => [
                        'font_size' => '12'
                    ],
                    'report_title' => [
                        'font_size' => null
                    ]
                ],
                'margins' => [
                    'top' => null,
                    'left' => null,
                    'right' => null,
                    'bottom' => null
                ],
                'comments' => [
                    'title' => [
                        'font_size' => null
                    ],
                    'content' => [
                        'font_size' => null
                    ],
                    'signature' => [
                        'font_size' => null
                    ],
                    'line_height' => null,
                    'section_gap' => '8',
                    'title_margin' => null,
                    'section_spacing' => null,
                    'signature_height' => null,
                    'container_padding' => null
                ],
                'activities' => [
                    'content' => [
                        'font_size' => '8'
                    ],
                    'table_gap' => '6',
                    'row_height' => '10',
                    'line_height' => '0.8',
                    'rating_size' => '12',
                    'grading_scale' => [
                        'font_size' => '8',
                        'cell_padding' => null
                    ],
                    'section_title' => [
                        'font_size' => '10'
                    ],
                    'rating_row_height' => '10',
                    'table_row_spacing' => '4',
                    'table_cell_padding' => '4',
                    'table_margin_bottom' => '8'
                ],
                'paper_size' => null,
                'grades_table' => [
                    'cells' => [
                        'padding' => '6',
                        'font_size' => '11'
                    ],
                    'header' => [
                        'font_size' => null
                    ],
                    'line_height' => '1',
                    'margin_bottom' => '8',
                    'container_padding' => null
                ],
                'student_info' => [
                    'title' => [
                        'font_size' => '11'
                    ],
                    'labels' => [
                        'font_size' => '10'
                    ],
                    'values' => [
                        'font_size' => '10'
                    ],
                    'line_height' => '1',
                    'row_spacing' => null,
                    'section_gap' => '6',
                    'title_margin' => '4',
                    'container_padding' => null
                ]
            ]),

        ]);
    }
}

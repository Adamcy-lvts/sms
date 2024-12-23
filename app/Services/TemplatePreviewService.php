<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\ReportTemplate;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\View;

class TemplatePreviewService
{

    public function generatePreview(ReportTemplate $template): string
    {
        $school = Filament::getTenant();

        // Get current session and term
        // $currentSession = $school->academicSessions()
        //     ->where('is_current', true)
        //     ->first();

        // $currentTerm = $currentSession?->terms()
        //     ->where('is_current', true)
        //     ->first();

        return View::make('report-cards.preview', [
            'template' => $template,
            'data' => $this->getSampleData(),
            'school' => $school,
        ])->render();
    }

    public function getSampleData(): array
    {
        $school = Filament::getTenant();
        // Get current session and term
        // Get current session and term
        $currentSession = $school->academicSessions()
            ->where('is_current', true)
            ->first();

        $currentTerm = $currentSession?->terms()
            ->where('is_current', true)
            ->first();

        return [
            'basic_info' => [
                'name' => 'John Doe',
                'admission_number' => 'ADM/2023/001',
                'class' => 'JSS 1A',
                'gender' => 'Male',
                'date_of_birth' => '2010-05-15',
                'age' => '13 years'
            ],
            'attendance' => [
                'school_days' => 120,
                'present' => 115,
                'absent' => 5,
                'percentage' => 95.8
            ],
            'term_summary' => [
                'total_score' => 534,
                'average' => 76.3,
                'position' => '5th',
                'class_size' => 45
            ],
            'academic_info' => [
                'session' => $currentSession?->name,
                'term' => $currentTerm?->name,
                'session_name' => $currentSession?->name,
                'term_name' => $currentTerm?->name,
            ],

            'subjects' => [
                [
                    'name' => 'Mathematics',
                    'assessment_columns' => ['ca1' => 9, 'ca2' => 8, 'ca3' => 10, 'exam' => 50],
                    'total' => 78,
                    'grade' => 'A',
                    'remark' => 'Excellent'
                ],
                [
                    'name' => 'English',
                    'assessment_columns' => ['ca1' => 4, 'ca2' => 6, 'ca3' => 10, 'exam' => 60],
                    'total' => 70,
                    'grade' => 'B',
                    'remark' => 'Very Good'
                ],
                [
                    'name' => 'Physics',
                    'assessment_columns' => ['ca1' => 8, 'ca2' => 7, 'ca3' => 9, 'exam' => 55],
                    'total' => 79,
                    'grade' => 'A',
                    'remark' => 'Excellent'
                ],
                [
                    'name' => 'Chemistry',
                    'assessment_columns' => ['ca1' => 7, 'ca2' => 8, 'ca3' => 8, 'exam' => 52],
                    'total' => 75,
                    'grade' => 'A',
                    'remark' => 'Excellent'
                ],
                [
                    'name' => 'Biology',
                    'assessment_columns' => ['ca1' => 6, 'ca2' => 7, 'ca3' => 9, 'exam' => 48],
                    'total' => 70,
                    'grade' => 'B',
                    'remark' => 'Very Good'
                ],
                [
                    'name' => 'Geography',
                    'assessment_columns' => ['ca1' => 8, 'ca2' => 8, 'ca3' => 7, 'exam' => 50],
                    'total' => 73,
                    'grade' => 'B',
                    'remark' => 'Very Good'
                ],
                [
                    'name' => 'History',
                    'assessment_columns' => ['ca1' => 9, 'ca2' => 8, 'ca3' => 8, 'exam' => 53],
                    'total' => 78,
                    'grade' => 'A',
                    'remark' => 'Excellent'
                ],
                [
                    'name' => 'Computer Science',
                    'assessment_columns' => ['ca1' => 8, 'ca2' => 9, 'ca3' => 9, 'exam' => 58],
                    'total' => 84,
                    'grade' => 'A',
                    'remark' => 'Excellent'
                ],
                [
                    'name' => 'Economics',
                    'assessment_columns' => ['ca1' => 7, 'ca2' => 7, 'ca3' => 8, 'exam' => 51],
                    'total' => 73,
                    'grade' => 'B',
                    'remark' => 'Very Good'
                ],
                [
                    'name' => 'Literature',
                    'assessment_columns' => ['ca1' => 6, 'ca2' => 8, 'ca3' => 7, 'exam' => 49],
                    'total' => 70,
                    'grade' => 'B',
                    'remark' => 'Very Good'
                ]
            ],

            'comments' => $this->getCommentsData(),

            // 'activities' => [
            //     'Sports Activities' => [
            //         'Football' => [
            //             'grade' => 'A',
            //             'description' => 'Excellent team player and leadership skills'
            //         ],
            //         'Basketball' => [
            //             'grade' => 'B',
            //             'description' => 'Good coordination and improving skills'
            //         ],
            //         'Athletics' => [
            //             'grade' => 'A',
            //             'description' => 'Outstanding performance in track events'
            //         ]
            //     ],
            //     'Cultural Activities' => [
            //         'Drama' => [
            //             'rating' => 4,
            //             'description' => 'Active participation in school plays'
            //         ],
            //         'Music' => [
            //             'rating' => 5,
            //             'description' => 'Exceptional vocal and instrumental skills'
            //         ],
            //         'Art' => [
            //             'rating' => 3,
            //             'description' => 'Shows creativity and enthusiasm'
            //         ]
            //     ],
            //     'Clubs & Societies' => [
            //         'Science Club' => [
            //             'boolean' => true,
            //             'description' => 'Active member, participated in science fair'
            //         ],
            //         'Debate Club' => [
            //             'boolean' => true,
            //             'description' => 'Won inter-house debate competition'
            //         ],
            //         'Environmental Club' => [
            //             'boolean' => false,
            //             'description' => 'Registered member'
            //         ]
            //     ]
            // ]

        ];
    }

    protected function getCommentsData(): array
    {
        $template = request()->route('record');
        if (!$template) {
            return [];
        }

        $commentsConfig = $template->getCommentsConfig();

        // Check if comments section is enabled globally
        if (!($commentsConfig['enabled'] ?? false)) {
            return [];
        }

        $comments = [];

        foreach ($commentsConfig['sections'] as $section) {
            // Skip if section is disabled
            if (!($section['enabled'] ?? true)) {
                continue;
            }

            $key = Str::slug($section['title']);

            switch ($section['type']) {
                case 'free_text':
                    $comments[$key] = [
                        'type' => 'free_text',
                        'content' => $section['format'] ?? 'No comment provided.',
                    ];
                    break;

                case 'predefined':
                    if (!empty($section['predefined_comments'])) {
                        $firstComment = array_values($section['predefined_comments'])[0];
                        $comments[$key] = [
                            'type' => 'predefined',
                            'content' => $firstComment,
                            'code' => array_key_first($section['predefined_comments'])
                        ];
                    }
                    break;

                case 'rating':
                    $comments[$key] = [
                        'type' => 'rating',
                        'content' => [
                            'value' => ($section['rating_scale']['max'] ?? 5) - 1,
                            'max' => $section['rating_scale']['max'] ?? 5,
                            'display' => $section['rating_scale']['display'] ?? 'numeric'
                        ]
                    ];
                    break;
            }
        }

        return $comments;
    }
}

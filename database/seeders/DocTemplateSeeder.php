<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DocTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Basic Admission Letter Template
        Template::create([
            'name' => 'Standard Admission Letter',
            'slug' => 'standard-admission-letter',
            'school_id' => 1, // Kings Private School ID
            'category' => 'admission_letter',
            'description' => 'Default admission letter template with school branding',
            'is_active' => true,
            'content' => json_encode([
                "type" => "doc",
                "content" => [
                    [
                        "type" => "heading",
                        "attrs" => ["level" => 1],
                        "content" => [
                            ["type" => "text", "text" => "{{school_name}}"]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Date: "],
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{admission_date}}"]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Dear "],
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{guardian_name}}"]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "We are pleased to inform you that "],
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{student_name}}"],
                            ["type" => "text", "text" => " has been granted admission to "],
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{class_name}}"],
                            ["type" => "text", "text" => " for the academic session {{academic_session}}."]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Admission Number: "],
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{admission_number}}"]
                        ]
                    ],
                    // Fee information section
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Please complete the admission process by:"],
                        ]
                    ],
                    [
                        "type" => "bulletList",
                        "content" => [
                            [
                                "type" => "listItem",
                                "content" => [
                                    [
                                        "type" => "paragraph",
                                        "content" => [
                                            ["type" => "text", "text" => "Paying the admission fee by {{fee_deadline}}"]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                "type" => "listItem",
                                "content" => [
                                    [
                                        "type" => "paragraph",
                                        "content" => [
                                            ["type" => "text", "text" => "Submitting all required documents"]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    // Closing
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Congratulations and welcome to {{school_name}}."]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "text" => "Sincerely,"]
                        ]
                    ],
                    [
                        "type" => "paragraph",
                        "content" => [
                            ["type" => "text", "marks" => [["type" => "bold"]], "text" => "{{principal_name}}"],
                            ["type" => "text", "text" => "\nPrincipal"]
                        ]
                    ]
                ]
            ])
        ]);

        // // Add Acceptance Letter Template
        // Template::create([
        //     'name' => 'Acceptance Letter',
        //     'slug' => 'acceptance-letter',
        //     'school_id' => 1,
        //     'category' => 'acceptance_letter',
        //     'is_active' => true,
        //     // Similar content structure
        // ]);

        // // Add Rejection Letter Template
        // Template::create([
        //     'name' => 'Application Status Update',
        //     'slug' => 'rejection-letter',
        //     'school_id' => 1,
        //     'category' => 'rejection_letter',
        //     'is_active' => true,
        //     // Similar content structure
        // ]);
    }
}

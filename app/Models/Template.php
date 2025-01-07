<?php

namespace App\Models;

use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Template extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'school_id',
        'content',
        'category',  // For organizing templates
        'description', // Brief description of template purpose
        'is_active',  // Enable/disable templates
        'is_default', // Default templates for schools
        'settings',   // JSON field for template-specific settings
        'version',    // Track template versions
        'last_edited_by', // Track who last modified
    ];

    protected $casts = [
        'content' => 'array',
        'settings' => 'array',
        'is_active' => 'boolean',
        'version' => 'integer',
    ];

    protected function getDefaultTemplateContent(): array 
    {
        return [
            "type" => "doc",
            "content" => [
                // ... previous content ...
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "text", 
                            "text" => "Sincerely,"
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "image",
                            "attrs" => [
                                "src" => "{{principal_signature}}",
                                "alt" => "Principal's Signature",
                                "title" => "Principal's Signature",
                                "style" => "max-height: 100px;"
                            ]
                        ]
                    ]
                ],
                [
                    "type" => "paragraph",
                    "content" => [
                        [
                            "type" => "text",
                            "text" => "{{principal_name}}\n"
                        ],
                        [
                            "type" => "text",
                            "text" => "Principal"
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getAvailableVariables(): array
    {
        return TemplateVariable::where('school_id', $this->school_id)
            ->where('is_active', true)
            ->when($this->category !== 'all', function ($query) {
                $query->where(function ($q) {
                    $q->where('category', 'all')
                        ->orWhere('category', $this->category);
                });
            })
            ->pluck('name')
            ->toArray();
    }

    // In Template model
    public static function categories(): array
    {
        return [
            'admission_letter' => 'Admission Letter',
            'acceptance_letter' => 'Acceptance Letter',
            'rejection_letter' => 'Rejection Letter',
            'fee_structure' => 'Fee Structure',
            'rules_regulations' => 'Rules & Regulations',
        ];
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}

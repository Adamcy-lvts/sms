<?php

namespace App\Services;

use App\Models\School;
use App\Models\ReportTemplate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DefaultReportTemplateService 
{
    // Define path where templates are stored
    protected $templatePath = 'reports-cards-templates';

    /**
     * Create default report templates for a school
     * 
     * @param School $school The school to create templates for
     * @return void
     */
    public function createDefaultTemplates(School $school): void 
    {
        try {
            // Get all JSON files from the templates directory
            $templates = Storage::files($this->templatePath);

            DB::transaction(function() use ($school, $templates) {
                foreach($templates as $template) {
                    // Read and decode JSON content
                    $config = json_decode(Storage::get($template), true);
                    
                    // Skip if JSON is invalid
                    if (!$config) {
                        Log::warning("Invalid JSON in template file: {$template}");
                        continue;
                    }

                    // Create template record
                    ReportTemplate::create([
                        'school_id' => $school->id,
                        'name' => $config['name'],
                        'slug' => Str::slug($config['name']), 
                        'description' => $config['description'],
                        'header_config' => $config['header_config'],
                        'student_info_config' => $config['student_info_config'],
                        'grade_table_config' => $config['grade_table_config'] ?? null, 
                        'activities_config' => $config['activities_config'] ?? null,
                        'comments_config' => $config['comments_config'] ?? null,
                        'print_config' => $config['print_config'] ?? null,
                        'rtl_config' => $config['rtl_config'] ?? null,
                        'is_default' => $config['is_default'] ?? false,
                        'is_active' => true,
                      
                    ]);
                }
            });

            Log::info("Created default templates for school: {$school->name}");

        } catch (\Exception $e) {
            Log::error("Failed to create default templates for school: {$school->name}", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
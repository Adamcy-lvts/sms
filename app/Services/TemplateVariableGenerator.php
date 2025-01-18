<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\TemplateVariable;

class TemplateVariableGenerator
{
    /**
     * Generate template variables from selected model fields
     */
    public static function generateFromFields(string $modelClass, array $selectedFields, int $schoolId): Collection
    {
        // Get model info from creator
        $modelData = collect(TemplateVariableCreator::getAvailableModels())
            ->firstWhere('model', $modelClass);

        // Create variables for each selected field
        return collect($selectedFields)->map(function ($fieldName) use ($modelData, $schoolId) {
            $field = $modelData['fields'][$fieldName];

            // Create new template variable
            return TemplateVariable::create([
                'school_id' => $schoolId,
                'name' => Str::snake($fieldName), // e.g. full_name
                'display_name' => $field['label'], // e.g. Full Name
                'category' => Str::snake(class_basename($modelData['model'])), // e.g. admission
                'field_type' => self::mapDatabaseTypeToFieldType($field['type']),
                'mapping' => Str::snake(class_basename($modelData['model'])) . '.' . $fieldName, // e.g. admission.full_name
                'is_system' => true,
                'is_active' => true
            ]);
        });
    }

    /**
     * Map database column types to template field types
     */
    public static function mapDatabaseTypeToFieldType(string $dbType): string
    {
        return match ($dbType) {
            'date', 'datetime', 'timestamp' => 'date',
            'integer', 'bigint', 'decimal', 'float' => 'number',
            'boolean' => 'boolean',
            'json', 'array' => 'json',
            'relation' => 'text', // Handle relation type
            default => 'text'
        };
    }

}

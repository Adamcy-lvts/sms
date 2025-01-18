<?php

namespace App\Services;

use Illuminate\Support\Str;
use App\Models\Designation;

use Illuminate\Support\Facades\Schema;
use App\Models\{Admission, Student, School, Staff, ClassRoom};

class TemplateVariableCreator
{

    protected static function getStaffDesignations(): array
    {
        // Cache this to avoid multiple DB calls
        return cache()->remember('staff_designations', now()->addHour(), function () {
            return Designation::query()
                ->select('id', 'name')
                ->get()
                ->mapWithKeys(function ($designation) {
                    // Convert designation name to a valid variable prefix
                    $role = Str::snake(Str::lower($designation->name));
                    return [$designation->name => $role];
                })
                ->toArray();
        });
    }

    // protected static function getModelFields($modelClass): array 
    // {
    //     $model = new $modelClass;
    //     $table = $model->getTable();
    //     $columns = Schema::getColumnListing($table);
    //     $context = self::getModelContext($modelClass);

    //     $fields = [];


    // }
    /**
     * Get all models and their fields that can be used for variables
     */
    public static function getAvailableModels(): array
    {
        return [
            'admission' => [
                'model' => Admission::class,
                'label' => 'Admission Data',
                'fields' => self::getModelFields(Admission::class)
            ],
            'student' => [
                'model' => Student::class,
                'label' => 'Student Information',
                'fields' => self::getModelFields(Student::class)
            ],
            'school' => [
                'model' => School::class,
                'label' => 'School Information',
                'fields' => self::getModelFields(School::class)
            ],
            'staff' => [
                'model' => Staff::class,
                'label' => 'Staff Information',
                'fields' => self::getModelFields(Staff::class)
            ],
        ];
    }

    /**
     * Get model context for prefixing variables
     */
    protected static function getModelContext(string $modelClass): string
    {
        return match ($modelClass) {
            Student::class => 'student',
            School::class => 'school',
            Staff::class => 'staff',
            Admission::class => 'admission',
            ClassRoom::class => 'class',
            default => Str::snake(class_basename($modelClass))
        };
    }

    /**
     * Check if field name is ambiguous and needs context
     */
    protected static function isAmbiguousField(string $column): bool
    {
        return in_array($column, [
            'name',
            'email',
            'phone',
            'address',
            'full_name',
            'first_name',
            'last_name',
            'middle_name',
            'phone_number',
            'mobile',
            'mobile_number',
            'date_of_birth',
            'gender',
            'city',
            'state',
            'country',
            'postal_code',
            'zip_code',
            'website',
            'status',
            'type',
            'description',
            'notes',
            'session',
            'term',
        ]);
    }

    /**
     * Get all model fields with proper context
     */
    protected static function getModelFields($modelClass): array
    {
        $model = new $modelClass;
        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);
        $context = self::getModelContext($modelClass);

        $fields = [];

        // If this is the Staff model, handle designation-specific fields
        if ($modelClass === Staff::class) {
            foreach (self::getStaffDesignations() as $designation => $role) {
                $fields["{$role}_name"] = [
                    'name' => "{$role}_name",
                    'label' => "{$designation} Name",
                    'type' => 'text',
                    'mapping' => "staff.{$designation}.full_name",
                    'designation' => $designation
                ];

                // Additional staff fields
                $fields["{$role}_phone"] = [
                    'name' => "{$role}_phone",
                    'label' => "{$designation} Phone",
                    'type' => 'text',
                    'mapping' => "staff.{$designation}.phone",
                    'designation' => $designation
                ];

                $fields["{$role}_email"] = [
                    'name' => "{$role}_email",
                    'label' => "{$designation} Email",
                    'type' => 'text',
                    'mapping' => "staff.{$designation}.email",
                    'designation' => $designation
                ];

                // Add signature field
                $fields["{$role}_signature"] = [
                    'name' => "{$role}_signature",
                    'label' => "{$designation} Signature",
                    'type' => 'image', // New field type for signatures
                    'mapping' => "staff.{$designation}.signature",
                    'designation' => $designation
                ];
            }

            return $fields;
        }

        foreach ($columns as $column) {
            if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            // Handle foreign key relationships
            if (str_ends_with($column, '_id')) {
                $baseRelation = str_replace('_id', '', $column);
                $relationName = Str::camel($baseRelation);

                if (method_exists($model, $relationName)) {
                    $fieldName = "{$context}_{$baseRelation}_name";

                    $fields[$fieldName] = [
                        'name' => $fieldName,
                        'label' => Str::title(str_replace('_', ' ', $context)) . ' ' .
                            Str::title(str_replace('_', ' ', $baseRelation)) . ' Name',
                        'type' => 'text', // Changed from 'relation' to 'text'
                        'relation' => $relationName,
                        'mapping' => "{$context}.{$baseRelation}_name"
                    ];
                    continue;
                }
            }

            // Handle regular fields with context if needed
            $fieldName = self::isAmbiguousField($column) ? "{$context}_{$column}" : $column;
            $fieldType = Schema::getColumnType($table, $column);

            // Map database types to our field types
            $type = match ($fieldType) {
                'date', 'datetime', 'timestamp' => 'date',
                'integer', 'bigint', 'decimal', 'float' => 'number',
                default => 'text'
            };

            $fields[$fieldName] = [
                'name' => $fieldName,
                'label' => self::isAmbiguousField($column)
                    ? Str::title(str_replace('_', ' ', $context)) . ' ' . Str::title(str_replace('_', ' ', $column))
                    : Str::title(str_replace('_', ' ', $column)),
                'type' => $type,
                'mapping' => "{$context}.{$column}"
            ];
        }

        return $fields;
    }
}

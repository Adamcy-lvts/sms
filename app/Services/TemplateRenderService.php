<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Template;
use App\Models\Admission;
use Illuminate\Support\Str;
use App\Models\TemplateVariable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TemplateRenderService
{
    protected $template;
    protected $school;

    public function __construct(Template $template)
    {
        $this->template = $template;
        $this->school = $template->school;
    }

    protected function resolveStaffValue(TemplateVariable $variable): mixed
    {
        Log::info('Resolving staff value:', [
            'variable' => $variable->name,
            'mapping' => $variable->mapping
        ]);

        // Extract designation and field from mapping (e.g., "staff.Principal.full_name")
        $parts = explode('.', $variable->mapping);
        if (count($parts) !== 3) {
            Log::warning('Invalid staff mapping format', ['mapping' => $variable->mapping]);
            return null;
        }

        [, $designation, $field] = $parts;

        // Find staff member with the given designation
        $staff = $this->school->staff()
            ->whereHas('designation', function ($query) use ($designation) {
                $query->where('name', $designation);
            })
            ->first();

        Log::info('Found staff:', [
            'designation' => $designation,
            'exists' => !is_null($staff),
            'staff_data' => $staff ? [
                'id' => $staff->id,
                'name' => $staff->full_name,
                'designation' => $staff->designation?->name
            ] : null
        ]);

        if (!$staff) {
            Log::warning("No staff found with designation: {$designation}");
            return null;
        }

        $value = $staff->$field;
        Log::info('Resolved staff value:', [
            'field' => $field,
            'value' => $value
        ]);

        return $this->formatValue($value, $variable->field_type);
    }



    public function renderForAdmission(Admission $admission): string
    {
        $variables = TemplateVariable::active()
            ->where('school_id', $this->school->id)
            ->get();

        Log::info('All variables to process:', $variables->toArray());

        $data = $variables->mapWithKeys(function ($variable) use ($admission) {
            Log::info('Processing variable:', [
                'name' => $variable->name,
                'mapping' => $variable->mapping,
                'field_type' => $variable->field_type
            ]);

            // Extract context and field from mapping
            [$context, $field] = explode('.', $variable->mapping);

            Log::info('Extracted mapping:', [
                'context' => $context,
                'field' => $field
            ]);

            // Get value based on context
            $value = match ($context) {
                'admission' => $this->resolveValue($admission, $field, $variable->field_type),
                'student' => $this->resolveValue($admission->student, $field, $variable->field_type),
                'school' => $this->resolveValue($this->school, $field, $variable->field_type),
                'class' => $this->resolveValue($admission->student?->classRoom, $field, $variable->field_type),
                'staff' => $this->resolveStaffValue($variable),
                default => null
            };

            Log::info('Resolved value:', [
                'variable' => $variable->name,
                'value' => $value
            ]);

            return [$variable->name => $value];
        });

        Log::info('Final data for template:', $data->toArray());

        return tiptap_converter()
            ->mergeTagsMap($data->toArray())
            ->asHTML($this->template->content);
    }

    protected function resolveValue($model, $field, $fieldType): mixed
    {
        if (!$model) return null;

        // Check if this is ACTUALLY a relationship field (ends with _name AND starts with a relationship context)
        if (
            str_ends_with($field, '_name') &&
            (str_starts_with($field, 'class_room_') ||
                str_starts_with($field, 'school_'))
        ) {

            Log::info('Processing relationship field');

            $relationName = Str::camel(str_replace('_name', '', $field));
            Log::info('Relation name:', ['relation' => $relationName]);

            if (method_exists($model, $relationName)) {
                Log::info('Relation method exists');
                $relatedModel = $model->{$relationName};

                Log::info('Related model:', [
                    'exists' => !is_null($relatedModel),
                    'type' => $relatedModel ? get_class($relatedModel) : 'null'
                ]);

                if ($relatedModel) {
                    // Try to get the most appropriate name field
                    $value = $relatedModel->name ??
                        $relatedModel->full_name ??
                        $relatedModel->title;

                    Log::info('Found name value:', ['value' => $value]);
                    return $value;
                }
            }
            return null;
        }

        // Get the raw value
        $value = $model->$field;
        Log::info('Raw value:', ['field' => $field, 'value' => $value]);

        // Format the value based on field type
        $formattedValue = $this->formatValue($value, $fieldType);
        Log::info('Formatted value:', ['value' => $formattedValue]);

        return $formattedValue;
    }


    protected function getPrincipal()
    {
        $principal = $this->school->staff()
            ->whereHas('designation', fn($q) => $q->where('name', 'Principal'))
            ->first();

        Log::info('Retrieved principal:', [
            'exists' => !is_null($principal),
            'data' => $principal ? $principal->toArray() : null
        ]);

        return $principal;
    }

    protected function formatValue($value, $fieldType): mixed
    {
        if (is_null($value)) return null;

        return match ($fieldType) {
            'date' => $this->formatDate($value),
            'number' => $this->formatNumber($value),
            default => $value
        };
    }

    protected function formatDate($value): ?string
    {
        if (!$value) return null;

        try {
            $date = $value instanceof Carbon
                ? $value
                : Carbon::parse($value);

            return $date->format('F j, Y'); // January 5, 2025
        } catch (\Exception $e) {
            return $value;
        }
    }

    protected function formatNumber($value): string
    {
        if (!is_numeric($value)) return $value;

        return number_format($value, 2);
    }

    // protected function getPrincipal()
    // {
    //     return $this->school->staff()
    //         ->whereHas('designation', fn($q) => $q->where('name', 'Principal'))
    //         ->first();
    // }
}

{{-- resources/views/report-cards/sections/activities.blade.php --}}
@php
    $activitiesConfig = $template->getActivitiesConfig();
    if (!($activitiesConfig['enabled'] ?? false)) {
        return;
    }

    // dd($activitiesConfig);
    // Typography and Spacing Configuration
    $fontSize = $activitiesConfig['table_style']['font_size'] ?? '0.875rem';
    $cellPadding = $activitiesConfig['table_style']['cell_padding'] ?? '0.75rem';
    $rowHeight = $activitiesConfig['table_style']['row_height'] ?? '2.5rem';

    // Base Styles
    $containerStyle = 'margin-bottom: 1rem;';

    $sectionStyle = match ($activitiesConfig['layout']) {
        'grid' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;',
        'flex' => 'display: flex; flex-wrap: wrap; gap: 1rem;',
        'side-by-side' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem;',
        'stacked' => 'display: flex; flex-direction: column; gap: 1rem;',
        default => '',
    };

    // Table Styles
    $tableContainerStyle = "
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        overflow: hidden;
        width: 100%;
    ";

    $tableStyle = "
        width: 100%;
        border-collapse: collapse;
        font-size: {$fontSize};
       
    ";

    $headerStyle = "
        font-size: 0.875rem;
        font-weight: 500;
        color: #111827;
        margin-bottom: 0.75rem;
        padding: 0.5rem;
        background-color: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    ";

    $thStyle = "
        padding: {$cellPadding};
        text-align: left;
        font-weight: 500;
        color: #6B7280;
        background-color: #f9fafb;
        height: {$rowHeight};
    ";

    $tdStyle = "
        padding: {$cellPadding};
        border-bottom: 1px solid #e5e7eb;
        text-align: left; 
        height: {$rowHeight};
    ";

    // // Add grade scale specific styles
    // $gradeScaleStyle = "
//     width: 100%;
//     max-width: 300px;
//     margin: 0 auto;
//     border: 1px solid #e5e7eb;
//     border-radius: 0.375rem;
//     overflow: hidden;
// ";

    // $gradeHeaderStyle =
    //     "
//     font-size: {$fontSize}px;
//     padding: " .
    //     ($printConfig['table_cell_padding'] ?? '8') .
    //     "px;
//     background-color: #f8fafc;
//     border-bottom: 1px solid #e5e7eb;
//     text-align: center;
//     font-weight: 600;
//     color: #374151;
// ";

    // $gradeRowStyle = "
//     font-size: {$fontSize}px;
//     text-align: center;
// ";

    // $gradeCellStyle =
    //     "
//     padding: " .
    //     ($printConfig['table_cell_padding'] ?? '4') .
    //     "px;
//     border-bottom: 1px solid #f3f4f6;
// ";

@endphp

<div style="display: flex; gap: 1.5rem; margin: 1rem 0;">
    @foreach ($report['activities'] as $section)
        @if ($section['enabled'])
            <div
                style="flex: 1; background: white; border-radius: 0.375rem; border: 1px solid #e5e7eb; overflow: hidden;">
                <div style="background: #f8fafc; padding: 0.5rem 0.75rem; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #374151;">{{ $section['title'] }}
                    </h3>
                </div>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #f9fafb;">
                            @foreach ($section['columns'] as $column)
                                <th
                                    style="padding: 0.4rem 0.5rem; text-align: {{ $column['key'] === 'rating' ? 'center' : 'left' }}; color: #6b7280; font-weight: 500; border-bottom: 1px solid #e5e7eb; font-size: 0.75rem;">
                                    {{ $column['label'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3" style="padding: 0;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    @php
                                        $fields = collect($section['fields'])->filter(fn($field) => $field['enabled']);
                                        $totalFields = $fields->count();
                                        $splitNeeded = $totalFields > 5;
                                        $firstColumn = $splitNeeded ? $fields->take(ceil($totalFields / 2)) : $fields;
                                        $secondColumn = $splitNeeded
                                            ? $fields->slice(ceil($totalFields / 2))
                                            : collect([]);
                                        $fontSize = $splitNeeded ? '0.7rem' : '0.75rem';
                                        $padding = $splitNeeded ? '0.25rem 0.4rem' : '0.4rem 0.75rem';
                                    @endphp
                                    <tr>
                                        <td style="width: {{ $splitNeeded ? '50%' : '100%' }}; vertical-align: top;">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                @foreach ($firstColumn as $field)
                                                    <tr>
                                                        <td
                                                            style="padding: {{ $padding }}; border-bottom: 1px solid #f3f4f6; font-size: {{ $fontSize }}; width: 40%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                            {{ $field['name'] }}
                                                        </td>
                                                        <td
                                                            style="padding: {{ $padding }}; text-align: {{ $field['style']['alignment'] }}; border-bottom: 1px solid #f3f4f6; width: 30%; white-space: nowrap;">
                                                            <span
                                                                style="color: {{ match ($field['style']['text_color']) {
                                                                    'primary' => '#60A5FA',
                                                                    'success' => '#34D399',
                                                                    'warning' => '#FBBF24',
                                                                    default => '#9CA3AF',
                                                                } }}; font-size: {{ $fontSize }}; letter-spacing: -0.5px;">
                                                                {{ str_repeat('★', $field['value']['rating']) }}
                                                                <span
                                                                    style="color: #E5E7EB;">{{ str_repeat('★', 5 - $field['value']['rating']) }}</span>
                                                            </span>
                                                        </td>
                                                        <td
                                                            style="padding: {{ $padding }}; border-bottom: 1px solid #f3f4f6; font-size: {{ $fontSize }}; width: 30%; white-space: nowrap;">
                                                            {{ $field['value']['performance'] }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>
                                        @if ($splitNeeded)
                                            <td
                                                style="width: 50%; vertical-align: top; border-left: 1px solid #f3f4f6;">
                                                <table style="width: 100%; border-collapse: collapse;">
                                                    @foreach ($secondColumn as $field)
                                                        <tr>
                                                            <td
                                                                style="padding: {{ $padding }}; border-bottom: 1px solid #f3f4f6; font-size: {{ $fontSize }}; width: 40%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                                {{ $field['name'] }}
                                                            </td>
                                                            <td
                                                                style="padding: {{ $padding }}; text-align: {{ $field['style']['alignment'] }}; border-bottom: 1px solid #f3f4f6; width: 30%; white-space: nowrap;">
                                                                <span
                                                                    style="color: {{ match ($field['style']['text_color']) {
                                                                        'primary' => '#60A5FA',
                                                                        'success' => '#34D399',
                                                                        'warning' => '#FBBF24',
                                                                        default => '#9CA3AF',
                                                                    } }}; font-size: {{ $fontSize }}; letter-spacing: -0.5px;">
                                                                    {{ str_repeat('★', $field['value']['rating']) }}
                                                                    <span
                                                                        style="color: #E5E7EB;">{{ str_repeat('★', 5 - $field['value']['rating']) }}</span>
                                                                </span>
                                                            </td>
                                                            <td
                                                                style="padding: {{ $padding }}; border-bottom: 1px solid #f3f4f6; font-size: {{ $fontSize }}; width: 30%; white-space: nowrap;">
                                                                {{ $field['value']['performance'] }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </table>
                                            </td>
                                        @endif
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

</div>
{{-- <div style="margin-top: 1rem; margin-bottom: 1rem;">
    <div style="padding: 0.5rem; background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 0.375rem; text-align: center; margin-bottom: 0.5rem;">
        <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #374151;">Grade Scale</h3>
    </div>
    <div style="display: flex; gap: 0.25rem;">
        @php
            $colorMap = [
                'A' => ['bg' => '#dcfce7', 'text' => '#059669', 'border' => '#86efac'],
                'B' => ['bg' => '#dbeafe', 'text' => '#2563eb', 'border' => '#93c5fd'],
                'C' => ['bg' => '#fef9c3', 'text' => '#ca8a04', 'border' => '#fde047'],
                'D' => ['bg' => '#fee2e2', 'text' => '#dc2626', 'border' => '#fca5a5'],
                'F' => ['bg' => '#fef2f2', 'text' => '#b91c1c', 'border' => '#fecaca'],
            ];
        @endphp

        @foreach (\App\Models\GradingScale::where('school_id', $school->id)->where('is_active', true)->orderBy('min_score', 'desc')->get() as $grade)
            @php
                $colors = $colorMap[$grade->grade] ?? ['bg' => '#f3f4f6', 'text' => '#374151', 'border' => '#e5e7eb'];
            @endphp
            <div
                style="flex: 1; background: {{ $colors['bg'] }}; border: 1px solid {{ $colors['border'] }}; border-radius: 0.375rem; padding: 0.35rem; text-align: center;">
                <div style="font-weight: 600; color: {{ $colors['text'] }}; font-size: 0.875rem;">{{ $grade->grade }}
                </div>
                <div style="color: {{ $colors['text'] }}; font-size: 0.7rem;">
                    {{ $grade->min_score }}-{{ $grade->max_score }}%</div>
                <div style="color: {{ $colors['text'] }}; font-size: 0.7rem;">{{ $grade->remark }}</div>
            </div>
        @endforeach
    </div>
</div> --}}

@php
    $config = $template->getGradeTableConfig();

    $getGradeColors = function ($minScore) use ($config) {
        $baseColor = match (true) {
            $minScore >= 70 => $config['colors']['excellent'] ?? '#15803d',
            $minScore >= 60 => $config['colors']['very_good'] ?? '#1e40af',
            $minScore >= 50 => $config['colors']['good'] ?? '#0369a1',
            $minScore >= 40 => $config['colors']['poor'] ?? '#d97706',
            default => $config['colors']['fail'] ?? '#dc2626',
        };

        // Convert hex to RGB for manipulation
        $hex = ltrim($baseColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Create lighter background (90% lighter)
        $bgR = $r + (255 - $r) * 0.9;
        $bgG = $g + (255 - $g) * 0.9;
        $bgB = $b + (255 - $b) * 0.9;

        // Create border color (70% lighter)
        $borderR = $r + (255 - $r) * 0.7;
        $borderG = $g + (255 - $g) * 0.7;
        $borderB = $b + (255 - $b) * 0.7;

        return [
            'text' => $baseColor,
            'bg' => sprintf('#%02x%02x%02x', $bgR, $bgG, $bgB),
            'border' => sprintf('#%02x%02x%02x', $borderR, $borderG, $borderB),
        ];
    };
@endphp

<div style="margin-top: 1rem; margin-bottom: 1rem;">
    <div
        style="padding: 0.5rem; background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 0.375rem; text-align: center; margin-bottom: 0.5rem;">
        <h3 style="margin: 0; font-size: 0.875rem; font-weight: 600; color: #374151;">Grade Scale</h3>
    </div>
    <div style="display: flex; gap: 0.25rem;">
        @foreach (\App\Models\GradingScale::where('school_id', $school->id)->where('is_active', true)->orderBy('min_score', 'desc')->get() as $grade)
            @php
                $colors = $getGradeColors($grade->min_score);
            @endphp
            <div
                style="flex: 1; background: {{ $colors['bg'] }}; border: 1px solid {{ $colors['border'] }}; border-radius: 0.375rem; padding: 0.35rem; text-align: center;">
                <div style="font-weight: 600; color: {{ $colors['text'] }}; font-size: 0.875rem;">{{ $grade->grade }}
                </div>
                <div style="color: {{ $colors['text'] }}; font-size: 0.7rem;">
                    {{ $grade->min_score }}-{{ $grade->max_score }}%</div>
                <div style="color: {{ $colors['text'] }}; font-size: 0.7rem;">{{ $grade->remark }}</div>
            </div>
        @endforeach
    </div>
</div>

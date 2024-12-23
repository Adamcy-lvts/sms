{{-- resources/views/report-cards/sections/grade-table.blade.php --}}
@php
    $config = $template->getGradeTableConfig();
    $rtlConfig = $template->getRtlConfig();

    // Convert spacing to rem units
    $spacing = match ($config['layout']['spacing'] ?? 'normal') {
        'compact' => '0.5rem',
        'relaxed' => '2rem',
        default => '1rem', // normal
    };
// dd($config['colors']['excellent']);
    // Get assessment columns
    $assessmentColumns = $template->grade_table_config['assessment_columns'] ?? [];


    $getScoreColor = function($score) use ($config) {
        return match(true) {
            $score >= 70 => $config['colors']['excellent'] ?? '#15803d',
            $score >= 60 => $config['colors']['very_good'] ?? '#1e40af',
            $score >= 50 => $config['colors']['good'] ?? '#0369a1',
            $score >= 40 => $config['colors']['poor'] ?? '#d97706',
            default => $config['colors']['fail'] ?? '#dc2626',
        };
    };

    // Table-wide styles
    $tableStyles =
        "
        width: 100%;
        border-collapse: collapse;
        margin-bottom: {$spacing};
        font-family: " .
        ($config['font_family'] ?? 'inherit') .
        ";
        font-size: " .
        ($config['font_size'] ?? '0.875rem') .
        ";
        line-height: " .
        ($config['line_height'] ?? '1.25') .
        "
    ";

    // Container styles
    $containerStyles =
        "
        padding: {$spacing};
          border: 1px solid #e5e7eb;
        border-radius: 0.2rem;
        background-color: " .
        ($config['layout']['background'] ?? '#ffffff') .
        ";
        margin-bottom: " .
        ($config['layout']['margin'] ?? '1.5rem') .
        ";
        border: " .
        ($config['layout']['border'] ?? '1px solid #e5e7eb') .
        ";
        border-radius: " .
        ($config['layout']['rounded'] ?? '0.5rem') .
        ";
        box-shadow: " .
        ($config['layout']['shadow'] ?? '0 1px 3px 0 rgba(0, 0, 0, 0.1)') .
        "
        
    ";

    // Container styles with premium border options
    $borderStyle = $config['border']['style'] ?? 'single';

    $premiumBorderStyles = match ($borderStyle) {
        'none' => '',
        'single' => "
        border: " .
            ($config['border']['width'] ?? '1px') .
            ' solid ' .
            ($config['border']['color'] ?? '#e5e7eb') .
            ";
        border-radius: " .
            ($config['border']['radius'] ?? '0.375rem') .
            ";
    ",
        'double' => "
        border: double " .
            ($config['border']['width'] ?? '3px') .
            ' ' .
            ($config['border']['color'] ?? '#e5e7eb') .
            ";
        border-radius: " .
            ($config['border']['radius'] ?? '0.375rem') .
            ";
    ",
        'premium' => "
        border: " .
            ($config['border']['width'] ?? '1px') .
            ' solid ' .
            ($config['border']['color'] ?? '#e5e7eb') .
            ";
        border-radius: " .
            ($config['border']['radius'] ?? '0.375rem') .
            ";
        box-shadow: 0 0 0 3px rgba(229, 231, 235, 0.15);
        position: relative;
    ",
        'modern' => "
        border: " .
            ($config['border']['width'] ?? '1px') .
            ' solid ' .
            ($config['border']['color'] ?? '#e5e7eb') .
            ";
        border-radius: " .
            ($config['border']['radius'] ?? '0.375rem') .
            ";
        box-shadow: 
            0 4px 6px -1px rgba(0, 0, 0, 0.1),
            0 2px 4px -1px rgba(0, 0, 0, 0.06);
    ",
        default => 'border: 1px solid #e5e7eb;',
    };

    // Base header styles
    $headerStyles =
        "
         padding: " .
        ($config['header']['padding'] ?? '0.5rem') .
        ";
        background-color: " .
        ($config['header']['background'] ?? '#f9fafb') .
        ";
        color: " .
        ($config['header']['text_color'] ?? '#374151') .
        ";
        font-weight: " .
        ($config['header']['font_weight'] ?? '600') .
        ";
        border-bottom: " .
        ($config['header']['border'] ?? '1px solid #e5e7eb') .
        ";
        font-size: " .
        ($config['header']['font_size'] ?? '0.875rem') .
        "
    ";

    // Base cell styles
    $cellStyles =
        "
        padding: " .
        ($config['rows']['padding'] ?? '0.5rem') .
        ";
        border-top: " .
        ($config['rows']['border'] ?? '1px solid #e5e7eb') .
        ";
        color: " .
        ($config['rows']['text_color'] ?? '#374151') .
        ";
        font-size: " .
        ($config['rows']['font_size'] ?? '0.875rem') .
        ";
        font-weight: " .
        ($config['rows']['font_weight'] ?? '600') .
        ";
    ";

    $tdFontWeight = $config['rows']['font_weight'] ?? '600';
// dd($tdFontWeight);
    // Column-specific styles
    $subjectHeaderStyle = $headerStyles . '; text-align: left;';
    $assessmentHeaderStyle = $headerStyles . '; text-align: center;';
    $totalHeaderStyle = $headerStyles . '; text-align: center;';
    $gradeHeaderStyle = $headerStyles . '; text-align: center;';
    $remarkHeaderStyle = $headerStyles . '; text-align: left;';

    $subjectCellStyle = $cellStyles . '; text-align: left;';
    $assessmentCellStyle = $cellStyles . '; text-align: center;';
    $totalCellStyle = $cellStyles . '; text-align: center;';
    $gradeCellStyle = $cellStyles . '; text-align: center;';
    $remarkCellStyle = $cellStyles . '; text-align: left;';
@endphp

<div style="{{ $containerStyles }}">
    @if ($config['show_title'] ?? true)
        <h3
            style="
            font-size: {{ $config['title_font_size'] ?? '1.125rem' }};
            font-weight: 600;
            margin-bottom: 1rem;
            color: {{ $config['title_color'] ?? '#111827' }};
        ">
            {{ $config['title'] ?? 'Academic Performance' }}
        </h3>
    @endif

    <table style="{{ $tableStyles }}">
        {{-- Header --}}
        @if ($config['header']['enabled'] ?? true)
            <thead>
                <tr>
                    {{-- Subject Column --}}
                    @if ($config['columns']['subject']['show'] ?? true)
                        <th
                            style="
                            {{ $subjectHeaderStyle }}
                            width: {{ $config['columns']['subject']['width'] ?? '12rem' }};
                        ">
                            {{ $config['columns']['subject']['name'] ?? 'Subject' }}
                        </th>
                    @endif

                    {{-- Assessment Columns --}}
                    @foreach ($assessmentColumns as $column)
                        @if ($column['show'] ?? true)
                            <th
                                style="
                                {{ $assessmentHeaderStyle }}
                                width: {{ $column['width'] ?? '5rem' }};
                            ">
                                {{ $column['name'] }}
                                @if ($column['show_max_score'] ?? true)
                                    <span
                                        style="font-size: 0.75rem; color: #6b7280;">({{ $column['max_score'] }})</span>
                                @endif
                            </th>
                        @endif
                    @endforeach

                    {{-- Total Column --}}
                    @if ($config['columns']['total']['enabled'] ?? true)
                        <th
                            style="
                            {{ $totalHeaderStyle }}
                            width: {{ $config['columns']['total']['width'] ?? '5rem' }};
                        ">
                            {{ $config['columns']['total']['name'] ?? 'Total' }}
                        </th>
                    @endif

                    {{-- Grade Column --}}
                    @if ($config['columns']['grade']['enabled'] ?? true)
                        <th
                            style="
                            {{ $gradeHeaderStyle }}
                            width: {{ $config['columns']['grade']['width'] ?? '4rem' }};
                        ">
                            {{ $config['columns']['grade']['name'] ?? 'Grade' }}
                        </th>
                    @endif

                    {{-- Remark Column --}}
                    @if ($config['columns']['remark']['enabled'] ?? true)
                        <th
                            style="
                            {{ $remarkHeaderStyle }}
                            width: {{ $config['columns']['remark']['width'] ?? '8rem' }};
                        ">
                            {{ $config['columns']['remark']['name'] ?? 'Remark' }}
                        </th>
                    @endif
                </tr>
            </thead>
        @endif

        {{-- Body --}}
        <tbody>
            @foreach ($data['subjects'] as $subject)

                @php
                    // Apply in table cells:
                    $scoreColor = $getScoreColor($subject['total']);
                @endphp
                <tr
                    style="{{ $loop->even ? 'background-color: ' . ($config['rows']['stripe_color'] ?? '#f9fafb') . ';' : '' }} font-weight: {{$tdFontWeight}};">
                    {{-- Subject Column --}}

                    @if ($config['columns']['subject']['show'] ?? true)
                        <td style="{{ $subjectCellStyle }} {{ $config['color_settings']['apply_to_subject'] ?? true ? "color: {$scoreColor};" : '' }};">
                            @if ($rtlConfig['subjects']['show_arabic_names'] ?? false)
                                @php
                                    $displayStyle = $rtlConfig['subjects']['display_style'] ?? 'brackets';
                                    $separator = $rtlConfig['subjects']['separator'] ?? 'brackets';

                                    // Build Arabic style string with safe access
                                    $arabicStyle = sprintf(
                                        'font-family: %s; direction: rtl; color: %s; %s font-size: %s;',
                                        $rtlConfig['arabic_font'] ?? 'Noto Naskh Arabic',
                                        $rtlConfig['subjects']['arabic_text_color'] ?? '#374151',
                                        $rtlConfig['subjects']['bold_arabic'] ?? false ? 'font-weight: bold;' : '',
                                        $rtlConfig['subjects']['arabic_text_size'] ?? '0.875rem',
                                    );

                                    // Define separators
                                    $separators = [
                                        'brackets' => ['(', ')'],
                                        'square_brackets' => ['[', ']'],
                                        'pipe' => ['| ', ''],
                                        'dash' => ['- ', ''],
                                        'none' => ['', ''],
                                    ];

                                    $separatorStart = $separators[$separator][0] ?? '';
                                    $separatorEnd = $separators[$separator][1] ?? '';
                                @endphp

                                @switch($displayStyle)
                                    @case('brackets')
                                        <span>{{ $subject['name'] }}</span>
                                        @if (!empty($subject['name_ar']))
                                            <span style="{{ $arabicStyle }} color: {{ $scoreColor }}; margin-right: 0.5rem;">
                                                {{ $separatorStart }}{{ $subject['name_ar'] }}{{ $separatorEnd }}
                                            </span>
                                        @endif
                                    @break

                                    @case('justified')
                                        <div style="display: flex; justify-content: space-between; align-items: center;">
                                            <span>{{ $subject['name'] }}</span>
                                            @if (!empty($subject['name_ar']))
                                                <span style="{{ $arabicStyle }} color: {{ $scoreColor }};">{{ $subject['name_ar'] }}</span>
                                            @endif
                                        </div>
                                    @break

                                    @case('newline')
                                        <div style="display: flex; flex-direction: column;">
                                            @if (!empty($subject['name_ar']))
                                                <span
                                                    style="{{ $arabicStyle }} color: {{ $scoreColor }}; text-align: right;">{{ $subject['name_ar'] }}</span>
                                            @endif
                                            <span>{{ $subject['name'] }}</span>
                                        </div>
                                    @break

                                    @case('separate')
                                        @if ($rtlConfig['subjects']['arabic_column_position'] === 'before')
                                            @if (!empty($subject['name_ar']))
                                                <span
                                                    style="{{ $arabicStyle }} color: {{ $scoreColor }}; margin-right: 0.5rem;">{{ $subject['name_ar'] }}</span>
                                            @endif
                                            <span>{{ $subject['name'] }}</span>
                                        @else
                                            <span>{{ $subject['name'] }}</span>
                                            @if (!empty($subject['name_ar']))
                                                <span
                                                    style="{{ $arabicStyle }} color: {{ $scoreColor }}; margin-right: 0.5rem;">{{ $subject['name_ar'] }}</span>
                                            @endif
                                        @endif
                                    @break

                                    @default
                                        {{ $subject['name'] }}
                                @endswitch
                            @else
                                {{ $subject['name'] }}
                            @endif
                        </td>
                    @endif

                    {{-- Assessment Columns --}}
                    @foreach ($assessmentColumns as $column)
                        @if ($column['show'] ?? true)
                            <td style="{{ $assessmentCellStyle }}">
                                {{ $subject['assessment_columns'][$column['key']] ?? '-' }}
                            </td>
                        @endif
                    @endforeach

                    {{-- Total Column --}}
                    @if ($config['columns']['total']['enabled'] ?? true)
                        <td style="{{ $totalCellStyle }} {{ $config['color_settings']['apply_to_total'] ?? true ? "color: {$scoreColor};" : '' }};">{{ $subject['total'] }}</td>
                    @endif

                    {{-- Grade Column --}}
                    @if ($config['columns']['grade']['enabled'] ?? true)
                        <td style="{{ $gradeCellStyle }} {{ $config['color_settings']['apply_to_grade'] ?? true ? "color: {$scoreColor};" : '' }};">{{ $subject['grade'] }}</td>
                    @endif

                    {{-- Remark Column --}}
                    @if ($config['columns']['remark']['enabled'] ?? true)
                        <td style="{{ $remarkCellStyle }} {{ $config['color_settings']['apply_to_remark'] ?? true ? "color: {$scoreColor};" : '' }};">{{ $subject['remark'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>

        {{-- Footer --}}
        @if ($config['footer']['enabled'] ?? false)
            <tfoot
                style="
                background-color: {{ $config['footer']['background'] ?? '#f9fafb' }};
                border-top: {{ $config['footer']['border'] ?? '2px solid #e5e7eb' }};
            ">
                @if ($config['footer']['show_total_score'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }} font-weight: 600;">Total Score</td>
                        <td style="{{ $assessmentCellStyle }}" colspan="{{ count($assessmentColumns) }}">
                            {{ collect($data['subjects'])->sum('total') }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif

                @if ($config['footer']['show_average'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }} font-weight: 600;">Average</td>
                        <td style="{{ $assessmentCellStyle }}" colspan="{{ count($assessmentColumns) }}">
                            {{ number_format(collect($data['subjects'])->average('total'), 1) }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif

                @if ($config['footer']['show_position'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }} font-weight: 600;">Position</td>
                        <td style="{{ $assessmentCellStyle }}" colspan="{{ count($assessmentColumns) }}">
                            {{ $data['position'] ?? '-' }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif
            </tfoot>
        @endif
    </table>
</div>

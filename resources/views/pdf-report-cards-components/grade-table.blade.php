{{-- resources/views/pdf-report-cards-components/grade-table.blade.php --}}
@php
    $config = $template->getGradeTableConfig();
    $rtlConfig = $template->getRtlConfig();
    $printConfig = $template->print_config['grades_table'] ?? [];
    // Optimized spacing values
    $spacing = '0.2rem';

    // Get assessment columns
    $assessmentColumns = $template->grade_table_config['assessment_columns'] ?? [];

    $getScoreColor = function ($score) use ($config) {
        return match (true) {
            $score >= 70 => $config['colors']['excellent'] ?? '#15803d',
            $score >= 60 => $config['colors']['very_good'] ?? '#166534',
            $score >= 50 => $config['colors']['good'] ?? '#0369a1',
            $score >= 40 => $config['colors']['poor'] ?? '#d97706',
            default => $config['colors']['fail'] ?? '#dc2626',
        };
    };

    $tableStyles =
        "
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    font-size: " .
        ($printConfig['cells']['font_size'] ?? '10') .
        "px;
    line-height: " .
        ($printConfig['line_height'] ?? '1.2') .
        ";
";

    $containerStyles =
        "
    padding: " .
        ($printConfig['container_padding'] ?? '12') .
        "px;
    background-color: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.2rem;
    margin-bottom: " .
        ($printConfig['margin_bottom'] ?? '12') .
        "px;
";

    $headerStyles =
        "
    padding: " .
        ($printConfig['cells']['padding'] ?? '6') .
        "px;
    background-color: #f9fafb;
    color: #374151;
    font-weight: 600;
    border-bottom: 1px solid #e5e7eb;
    font-size: " .
        ($printConfig['header']['font_size'] ?? '11') .
        "px;
    white-space: nowrap;
";

    $cellStyles =
        "
    padding: " .
        ($printConfig['cells']['padding'] ?? '6') .
        "px;
    border-bottom: 1px solid #e5e7eb;
    color: #374151;
    font-size: " .
        ($printConfig['cells']['font_size'] ?? '10') .
        "px;
    line-height: " .
        ($printConfig['line_height'] ?? '1.2') .
        ";
    font-weight: 600;
";

    // Column-specific styles
    $subjectHeaderStyle = $headerStyles . '; text-align: left; width: 18%;';
    $assessmentHeaderStyle = $headerStyles . '; text-align: center; width: 8%;';
    $totalHeaderStyle = $headerStyles . '; text-align: center; width: 8%;';
    $gradeHeaderStyle = $headerStyles . '; text-align: center; width: 8%;';
    $remarkHeaderStyle = $headerStyles . '; text-align: left; width: 15%;';

    // Cell styles with specific alignments
    $subjectCellStyle = $cellStyles . '; text-align: left;';
    $assessmentCellStyle = $cellStyles . '; text-align: center;';
    $totalCellStyle = $cellStyles . '; text-align: center; font-weight: 500;';
    $gradeCellStyle = $cellStyles . '; text-align: center;';
    $remarkCellStyle = $cellStyles . '; text-align: left;';

    // Title style
    $titleStyle = "
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.2rem;
        color: #111827;
    ";

    // Footer styles
    $footerStyles = "
        background-color: #f9fafb;
        border-top: 1px solid #e5e7eb;
        font-size: 0.65rem;
        font-weight: 500;
    ";
@endphp

<div style="{{ $containerStyles }}">
    @if ($config['show_title'] ?? true)
        <h3 style="{{ $titleStyle }}">
            {{ $config['title'] ?? 'Academic Performance' }}
        </h3>
    @endif

    <table style="{{ $tableStyles }}">
        {{-- Header --}}
        @if ($config['header']['enabled'] ?? true)
            <thead>
                <tr>
                    {{-- Subject Column --}}
                    <th style="{{ $subjectHeaderStyle }}">
                        {{ $config['columns']['subject']['name'] ?? 'Subject' }}
                    </th>

                    {{-- Assessment Columns --}}
                    @foreach ($assessmentColumns as $column)
                        <th style="{{ $assessmentHeaderStyle }}">
                            {{ $column['name'] }}
                            @if ($column['show_max_score'] ?? true)
                                <span style="font-size: 0.6rem; color: #6b7280;">
                                    ({{ $column['max_score'] }})
                                </span>
                            @endif
                        </th>
                    @endforeach

                    {{-- Result Columns --}}
                    <th style="{{ $totalHeaderStyle }}">
                        {{ $config['columns']['total']['name'] ?? 'Total' }}
                    </th>
                    <th style="{{ $gradeHeaderStyle }}">
                        {{ $config['columns']['grade']['name'] ?? 'Grade' }}
                    </th>
                    <th style="{{ $remarkHeaderStyle }}">
                        {{ $config['columns']['remark']['name'] ?? 'Remark' }}
                    </th>
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

                <tr style="{{ $loop->even ? 'background-color: #f9fafb;' : '' }}">
                    {{-- <td style="{{ $subjectCellStyle }}">{{ $subject['name'] }}</td> --}}

                    @if ($config['columns']['subject']['show'] ?? true)
                        <td
                            style="{{ $subjectCellStyle }} {{ $config['color_settings']['apply_to_subject'] ?? true ? "color: {$scoreColor};" : '' }};">
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
                                                    style="{{ $arabicStyle }}; color: {{ $scoreColor }}; margin-right: 0.5rem;">{{ $subject['name_ar'] }}</span>
                                            @endif
                                            <span>{{ $subject['name'] }}</span>
                                        @else
                                            <span>{{ $subject['name'] }}</span>
                                            @if (!empty($subject['name_ar']))
                                                <span
                                                    style="{{ $arabicStyle }}; color: {{ $scoreColor }}; margin-right: 0.5rem;">{{ $subject['name_ar'] }}</span>
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

                    @foreach ($assessmentColumns as $column)
                        <td style="{{ $assessmentCellStyle }}">
                            {{ $subject['assessment_columns'][$column['key']] ?? '-' }}
                        </td>
                    @endforeach

                    <td
                        style="{{ $totalCellStyle }} {{ $config['color_settings']['apply_to_total'] ?? true ? "color: {$scoreColor};" : '' }};">
                        {{ $subject['total'] }}</td>
                    <td
                        style="{{ $gradeCellStyle }} {{ $config['color_settings']['apply_to_grade'] ?? true ? "color: {$scoreColor};" : '' }};">
                        {{ $subject['grade'] }}</td>
                    <td
                        style="{{ $remarkCellStyle }} {{ $config['color_settings']['apply_to_remark'] ?? true ? "color: {$scoreColor};" : '' }};">
                        {{ $subject['remark'] }}</td>
                </tr>
            @endforeach
        </tbody>

        {{-- Footer --}}
        @if ($config['footer']['enabled'] ?? false)
            <tfoot style="{{ $footerStyles }}">
                @if ($config['footer']['show_total_score'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }}font-weight: 600;">Total Score</td>
                        <td style="{{ $assessmentCellStyle }}" colspan="{{ count($assessmentColumns) }}">
                            {{ collect($data['subjects'])->sum('total') }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif

                @if ($config['footer']['show_average'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }}font-weight: 600;">Average</td>
                        <td style="{{ $assessmentCellStyle }}" colspan="{{ count($assessmentColumns) }}">
                            {{ number_format(collect($data['subjects'])->average('total'), 1) }}
                        </td>
                        <td colspan="3"></td>
                    </tr>
                @endif

                @if ($config['footer']['show_position'] ?? false)
                    <tr>
                        <td style="{{ $subjectCellStyle }}font-weight: 600;">Position</td>
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

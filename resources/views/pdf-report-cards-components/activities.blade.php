{{-- resources/views/pdf-report-cards-components/activities.blade.php --}}

@php
    $activitiesConfig = $template->getActivitiesConfig();
    if (!($activitiesConfig['enabled'] ?? false)) {
        return;
    }

    // Get print config with defaults
    $printConfig = $template->print_config['activities'] ?? [];

    // Font sizes and spacing with defaults
    $sectionTitleSize = $printConfig['section_title']['font_size'] ?? '12';
    $contentFontSize = $printConfig['content']['font_size'] ?? '10';
    $columnSpacing = $printConfig['column_spacing'] ?? '12';

    // Base styles without split column dependency
    $tableStyle = "
        width: 100%;
        border-collapse: collapse;
        font-size: {$contentFontSize}px;
    ";

    $thStyle =
        "
        padding: " .
        ($printConfig['table_cell_padding'] ?? '8') .
        "px;
        text-align: left;
        font-weight: 500;
        color: #6B7280;
        font-size: {$contentFontSize}px;
        border-bottom: 1px solid #e5e7eb;
    ";

    $tdStyle =
        "
        padding: " .
        ($printConfig['table_cell_padding'] ?? '8') .
        "px;
        border-bottom: 1px solid #f3f4f6;
        font-size: {$contentFontSize}px;
        line-height: " .
        ($printConfig['line_height'] ?? '1.2') .
        ";
    ";

    $sectionHeaderStyle =
        "
        margin: 0;
        padding: " .
        ($printConfig['section_padding'] ?? '8') .
        "px;
        font-size: {$sectionTitleSize}px;
        font-weight: 600;
        color: #374151;
        background-color: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
    ";

    $containerStyle =
        "
        display: flex;
        gap: {$columnSpacing}px;
        margin: " .
        ($printConfig['container_margin'] ?? '8') .
        "px 0;
    ";

    $sectionStyle =
        "
        flex: 1;
        background: white;
        border-radius: " .
        ($printConfig['border_radius'] ?? '4') .
        "px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    ";
@endphp

<div style="{{ $containerStyle }}">
    @foreach ($activitiesConfig['sections'] as $section)
        @if ($section['enabled'])
            <div style="{{ $sectionStyle }}">
                <h3 style="{{ $sectionHeaderStyle }}">{{ $section['title'] }}</h3>

                <table style="{{ $tableStyle }}">
                    <thead>
                        <tr>
                            @foreach ($section['columns'] as $column)
                                <th style="{{ $thStyle }}">{{ $column['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="3" style="padding: 0;">
                                <table style="{{ $tableStyle }}">
                                    <tr>
                                        @php
                                            $fields = collect($section['fields'])->filter(
                                                fn($field) => $field['enabled'],
                                            );
                                            $totalFields = $fields->count();
                                            $splitNeeded = $totalFields > 5;
                                            $firstColumn = $splitNeeded
                                                ? $fields->take(ceil($totalFields / 2))
                                                : $fields;
                                            $secondColumn = $splitNeeded
                                                ? $fields->slice(ceil($totalFields / 2))
                                                : collect([]);

                                            // Define split column style after we know if splitting is needed
                                            $splitColumnStyle =
                                                "
                                                width: " .
                                                ($splitNeeded ? '50%' : '100%') .
                                                ";
                                                vertical-align: top;
                                                " .
                                                ($splitNeeded ? 'border-left: 1px solid #f3f4f6;' : '') .
                                                "
                                            ";
                                        @endphp

                                        {{-- First Column --}}
                                        <td style="{{ $splitColumnStyle }}">
                                            <table style="{{ $tableStyle }}">
                                                @foreach ($firstColumn as $field)
                                                    <tr>
                                                        <td style="{{ $tdStyle }}; width: 40%;">
                                                            {{ $field['name'] }}</td>
                                                        <td
                                                            style="{{ $tdStyle }}; width: 30%; text-align: {{ $field['style']['alignment'] }};">
                                                            <span
                                                                style="color: {{ match ($field['style']['text_color']) {
                                                                    'primary' => '#60A5FA',
                                                                    'success' => '#34D399',
                                                                    'warning' => '#FBBF24',
                                                                    default => '#9CA3AF',
                                                                } }};">
                                                                {{ str_repeat('★', $field['value']['rating']) }}
                                                                <span
                                                                    style="color: #E5E7EB;">{{ str_repeat('★', 5 - $field['value']['rating']) }}</span>
                                                            </span>
                                                        </td>
                                                        <td style="{{ $tdStyle }}; width: 30%;">
                                                            {{ $field['value']['performance'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </table>
                                        </td>

                                        {{-- Second Column (if needed) --}}
                                        @if ($splitNeeded)
                                            <td style="{{ $splitColumnStyle }}">
                                                <table style="{{ $tableStyle }}">
                                                    @foreach ($secondColumn as $field)
                                                        <tr>
                                                            <td style="{{ $tdStyle }}; width: 40%;">
                                                                {{ $field['name'] }}</td>
                                                            <td
                                                                style="{{ $tdStyle }}; width: 30%; text-align: {{ $field['style']['alignment'] }};">
                                                                <span
                                                                    style="color: {{ match ($field['style']['text_color']) {
                                                                        'primary' => '#60A5FA',
                                                                        'success' => '#34D399',
                                                                        'warning' => '#FBBF24',
                                                                        default => '#9CA3AF',
                                                                    } }};">
                                                                    {{ str_repeat('★', $field['value']['rating']) }}
                                                                    <span
                                                                        style="color: #E5E7EB;">{{ str_repeat('★', 5 - $field['value']['rating']) }}</span>
                                                                </span>
                                                            </td>
                                                            <td style="{{ $tdStyle }}; width: 30%;">
                                                                {{ $field['value']['performance'] }}</td>
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
{{-- Add to the template's print configuration --}}
@php
    // $printConfig = $template->print_config['grade_scale'] ?? [
    //     'font_size' => [
    //         'grade' => '12',
    //         'details' => '10',
    //     ],
    //     'padding' => '8',
    //     'margin' => '12',
    //     'spacing' => '4',
    // ];
    $printConfig = $template->print_config['activities'] ?? [];
    // dd($printConfig);
    $config = $template->getGradeTableConfig();

    $getGradeColors = function ($minScore) use ($config) {
        $baseColor = match (true) {
            $minScore >= 70 => $config['colors']['excellent'] ?? '#15803d',
            $minScore >= 60 => $config['colors']['very_good'] ?? '#1e40af',
            $minScore >= 50 => $config['colors']['good'] ?? '#0369a1',
            $minScore >= 40 => $config['colors']['poor'] ?? '#d97706',
            default => $config['colors']['fail'] ?? '#dc2626',
        };

        // Convert hex to RGB and create lighter versions
        $hex = ltrim($baseColor, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $bgR = $r + (255 - $r) * 0.9;
        $bgG = $g + (255 - $g) * 0.9;
        $bgB = $b + (255 - $b) * 0.9;

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

<div style="margin-bottom:8px;">
    <div
        style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px; margin-bottom: 4px; text-align: center;">
        <h3
            style="margin: 0; font-size: {{ $printConfig['section_title']['font_size'] }}px; font-weight: 600; color: #374151;">
            Grade Scale</h3>
    </div>
    <div style="display: flex; gap: 4px;">
        @foreach (\App\Models\GradingScale::where('school_id', $school->id)->where('is_active', true)->orderBy('min_score', 'desc')->get() as $grade)
            @php
                $colors = $getGradeColors($grade->min_score);
            @endphp
            <div
                style="flex: 1; background: {{ $colors['bg'] }}; border: 1px solid {{ $colors['border'] }}; border-radius: 4px; padding: 8px; text-align: center;">
                <div
                    style="font-weight: 600; color: {{ $colors['text'] }}; font-size: {{ $printConfig['grading_scale']['font_size'] }}px;">
                    {{ $grade->grade }}</div>
                <div style="color: {{ $colors['text'] }}; font-size: {{$contentFontSize}}px;">
                    {{ $grade->min_score }}-{{ $grade->max_score }}%</div>
                <div style="color: {{ $colors['text'] }}; font-size: {{$contentFontSize}}px;">
                    {{ $grade->remark }}</div>
            </div>
        @endforeach
    </div>
</div>

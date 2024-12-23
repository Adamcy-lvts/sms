@php
    // Get print config
    $printConfig = $template->print_config ?? [];

    $defaultStyles = $config['default_styles'] ?? [];
    $sectionStyles = $section['use_custom_styles'] ?? false ? $section : $defaultStyles;

    // Get background style settings
    $backgroundColor = $defaultStyles['stripe_color'] ?? '#f9fafb';
    $backgroundStyle = $defaultStyles['background_style'] ?? 'none';

    // Container styles with explicit border and padding
    $containerStyle = "
        padding: 0.2rem;
        box-sizing: border-box;
        border: 1px solid #d1d5db;
        border-radius: 0.2rem;
        background-color: $backgroundColor;
        margin-bottom: 0.2rem;
    ";

    $titleStyle =
        "
        font-size: " .
        ($printConfig['student_info']['title']['font_size'] ?? '11') .
        "px;
        font-weight: 600;
        margin-bottom: " .
        ($printConfig['student_info']['title_margin'] ?? '4') .
        "px;
        color: #111827;
        padding-left: 4px;
    ";

    $labelStyle =
        "
        font-size: " .
        ($printConfig['student_info']['labels']['font_size'] ?? '9') .
        "px;
        line-height: " .
        ($printConfig['student_info']['line_height'] ?? '1.1') .
        ";
        padding: " .
        ($printConfig['student_info']['row_spacing'] ?? '2') .
        "px 0;
    ";

    $valueStyle =
        "
        font-size: " .
        ($printConfig['student_info']['values']['font_size'] ?? '9') .
        "px;
        line-height: " .
        ($printConfig['student_info']['line_height'] ?? '1.1') .
        ";
        padding: " .
        ($printConfig['student_info']['row_spacing'] ?? '2') .
        "px 0;
        text-align: right;
    ";

    // Spacing for table rows
    $rowStyle = "
        border-bottom: 1px solid #e5e7eb;
        padding-top: 0.15rem;
        padding-bottom: 0.15rem;
    ";

    // Table styles
    $tableStyle = "
        width: 100%;
        border-collapse: collapse;
        border-spacing: 0;
    ";

    // Border styles
    $borderColor = $sectionStyles['border_color'] ?? ($defaultStyles['border_color'] ?? '#e5e7eb');
    $borderStyles = match ($sectionStyles['border_style'] ?? ($defaultStyles['border_style'] ?? 'none')) {
        'divider' => "border: none; border-bottom: 1px solid {$borderColor};",
        'full' => "border: 1px solid {$borderColor}; border-radius: 0.5rem;",
        'both' => "border: 1px solid {$borderColor}; border-radius: 0.5rem; border-bottom: 1px solid {$borderColor};",
        default => '',
    };

    $labelColor = $sectionStyles['label_color'] ?? ($defaultStyles['label_color'] ?? '#4B5563');
    $valueColor = $sectionStyles['value_color'] ?? ($defaultStyles['value_color'] ?? '#111827');

    // Background style for striped rows
    $rowBackgroundStyle = $backgroundStyle === 'striped' ? "background-color: {$backgroundColor};" : '';
@endphp

<div style="{{ $containerStyle }} {{ $borderStyles }}">
    @if (!empty($section['title']))
        <h3 style="{{ $titleStyle }}">{{ $section['title'] }}</h3>
    @endif

    @if (($section['layout'] ?? 'table') === 'table')
        <table style="{{ $tableStyle }}">
            <tbody>
                @foreach ($section['fields'] ?? [] as $index => $field)
                    @if ($field['enabled'] ?? true)
                        <tr
                            style="{{ $rowStyle }} {{ $backgroundStyle === 'striped' && $index % 2 !== 0 ? $rowBackgroundStyle : '' }}">
                            <td style="{{ $labelStyle }}; color: {{ $labelColor }};">
                                {{ $field['label'] ?? '' }}
                            </td>
                            <td style="{{ $valueStyle }}; color: {{ $valueColor }};">
                                @php
                                    $value = '';
                                    $sectionKey = $section['key'] ?? '';
                                    $fieldKey = $field['key'] ?? '';

                                    if ($sectionKey === 'attendance' && $fieldKey === 'percentage') {
                                        $value = isset($data[$sectionKey][$fieldKey])
                                            ? number_format($data[$sectionKey][$fieldKey], 1) . '%'
                                            : '-';
                                    } else {
                                        $value = $data[$sectionKey][$fieldKey] ?? '-';
                                    }
                                @endphp
                                {{ $value }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @else
        <div
            style="display: grid; grid-template-columns: repeat({{ $section['columns'] ?? 2 }}, 1fr); gap: 0.5rem; padding: 0.15rem;">
            @foreach ($section['fields'] ?? [] as $index => $field)
                @if ($field['enabled'] ?? true)
                    <div
                        style="{{ ($field['width'] ?? '') === 'full' ? 'grid-column: 1 / -1;' : '' }} padding: 0.15rem; {{ $backgroundStyle === 'striped' && $index % 2 !== 0 ? $rowBackgroundStyle : '' }}">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: {{ $labelColor }}; {{ $labelStyle }}">
                                {{ $field['label'] ?? '' }}
                            </span>
                            <span style="color: {{ $valueColor }}; {{ $valueStyle }}">
                                @php
                                    $value = '';
                                    $sectionKey = $section['key'] ?? '';
                                    $fieldKey = $field['key'] ?? '';

                                    if ($sectionKey === 'attendance' && $fieldKey === 'percentage') {
                                        $value = isset($data[$sectionKey][$fieldKey])
                                            ? number_format($data[$sectionKey][$fieldKey], 1) . '%'
                                            : '-';
                                    } else {
                                        $value = $data[$sectionKey][$fieldKey] ?? '-';
                                    }
                                @endphp
                                {{ $value }}
                            </span>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

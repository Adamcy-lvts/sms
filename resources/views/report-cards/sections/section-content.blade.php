{{-- resources/views/report-cards/sections/section-content.blade.php --}}
@php
    $defaultStyles = $config['default_styles'] ?? [];
    $sectionStyles = $section['use_custom_styles'] ?? false ? $section : $defaultStyles;

    // Get style configurations
    $titleSize = match ($sectionStyles['title_size'] ?? ($defaultStyles['title_size'] ?? 'text-base')) {
        'text-sm' => 'font-size: 0.875rem; line-height: 1.25rem;',
        'text-base' => 'font-size: 1rem; line-height: 1.5rem;',
        'text-lg' => 'font-size: 1.125rem; line-height: 1.75rem;',
        'text-xl' => 'font-size: 1.25rem; line-height: 1.75rem;',
        default => 'font-size: 1rem; line-height: 1.5rem;',
    };

    $labelSize = match ($sectionStyles['label_size'] ?? ($defaultStyles['label_size'] ?? 'text-sm')) {
        'text-xs' => 'font-size: 0.75rem; line-height: 1rem;',
        'text-sm' => 'font-size: 0.875rem; line-height: 1.25rem;',
        'text-base' => 'font-size: 1rem; line-height: 1.5rem;',
        default => 'font-size: 0.875rem; line-height: 1.25rem;',
    };

    $valueSize = match ($sectionStyles['value_size'] ?? ($defaultStyles['value_size'] ?? 'text-sm')) {
        'text-xs' => 'font-size: 0.75rem; line-height: 1rem;',
        'text-sm' => 'font-size: 0.875rem; line-height: 1.25rem;',
        'text-base' => 'font-size: 1rem; line-height: 1.5rem;',
        default => 'font-size: 0.875rem; line-height: 1.25rem;',
    };

    $spacing = match ($sectionStyles['spacing'] ?? ($defaultStyles['spacing'] ?? 'py-1.5')) {
        'py-1' => 'padding-top: 0.25rem; padding-bottom: 0.25rem;',
        'py-1.5' => 'padding-top: 0.375rem; padding-bottom: 0.375rem;',
        'py-2' => 'padding-top: 0.5rem; padding-bottom: 0.5rem;',
        default => 'padding-top: 0.375rem; padding-bottom: 0.375rem;',
    };

    $labelColor = $sectionStyles['label_color'] ?? ($defaultStyles['label_color'] ?? '#4B5563');
    $valueColor = $sectionStyles['value_color'] ?? ($defaultStyles['value_color'] ?? '#111827');

    // Border styles
    $borderColor = $sectionStyles['border_color'] ?? ($defaultStyles['border_color'] ?? '#e5e7eb');
    $borderStyles = match ($sectionStyles['border_style'] ?? ($defaultStyles['border_style'] ?? 'none')) {
        'divider' => "border: none; border-bottom: 1px solid {$borderColor};",
        'full' => "border: 1px solid {$borderColor}; border-radius: 0.5rem;",
        'both' => "border: 1px solid {$borderColor}; border-radius: 0.5rem; border-bottom: 1px solid {$borderColor};",
        default => '',
    };

    // Background styles with proper semicolons
    $stripeColor = $sectionStyles['stripe_color'] ?? ($defaultStyles['stripe_color'] ?? '#f9fafb');
    $hoverColor = $sectionStyles['hover_color'] ?? ($defaultStyles['hover_color'] ?? '#f3f4f6');

    $backgroundStyles = match ($sectionStyles['background_style'] ?? ($defaultStyles['background_style'] ?? 'none')) {
        'striped'
            => "--stripe-color: {$stripeColor}; background: linear-gradient(var(--stripe-color) 0%, var(--stripe-color) 100%);",
        'hover' => "--hover-color: {$hoverColor}; transition: background-color 0.2s;",
        'both'
            => "--stripe-color: {$stripeColor}; --hover-color: {$hoverColor}; background: linear-gradient(var(--stripe-color) 0%, var(--stripe-color) 100%); transition: background-color 0.2s;",
        default => '',
    };

    // Padding configurations
    $containerPadding = isset($config['padding']['container'])
        ? "padding: {$config['padding']['container']};"
        : 'padding: 0.5rem;';
    $gridPadding = isset($config['padding']['grid']) ? "padding: {$config['padding']['grid']};" : 'padding: 0.3rem;';
    $rowPadding = isset($config['padding']['row']) ? "padding: {$config['padding']['row']};" : 'padding: 0.5rem;';

    // Combined container styles
    $containerStyle = implode(' ', array_filter([$containerPadding, $borderStyles, $backgroundStyles]));

    // Generate unique IDs for scoped styles
    $uniqueId = 'section-' . uniqid();
@endphp


<div style="{{ $containerStyle }}" class="{{ $uniqueId }} dark:bg-gray-800">
    @if (!empty($section['title']))
        <h3 style="font-weight: 600; margin-bottom: 0.5rem; padding-left:0.5rem; {{ $titleSize }}">
            {{ $section['title'] }}</h3>
    @endif

    @if (($section['layout'] ?? 'table') === 'table')
        <table style="width: 100%; border-collapse: collapse;" class="dark:bg-gray-800">
            <tbody>
                @foreach ($section['fields'] ?? [] as $field)
                    @if ($field['enabled'] ?? true)
                        <tr class="table-row" style="{{ $spacing }}">
                            <td style="padding: 0.5rem 0; color: {{ $labelColor }}; {{ $labelSize }}">
                                {{ $field['label'] ?? '' }}
                            </td>
                            <td
                                style="padding: 0.5rem 0; color: {{ $valueColor }}; text-align: right; {{ $valueSize }}">
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
            style="display: grid; grid-template-columns: repeat({{ $section['columns'] ?? 2 }}, 1fr); gap: 1rem; {{ $gridPadding }}">
            @foreach ($section['fields'] ?? [] as $field)
                @if ($field['enabled'] ?? true)
                    <div class="grid-item "
                        style="{{ ($field['width'] ?? '') === 'full' ? 'grid-column: 1 / -1;' : '' }} {{ $spacing }}">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: {{ $labelColor }}; {{ $labelSize }}">
                                {{ $field['label'] ?? '' }}
                            </span>
                            <span class="dark:text-gray-300" style="color: {{ $valueColor }}; {{ $valueSize }}">
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

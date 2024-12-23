{{-- resources/views/pdf-report-cards-components/student-info.blade.php --}}
@php

    $config = $template->getStudentInfoConfig();
    $layout = $config['layout'] ?? 'single';
    $spacing = '0.2rem'; // Reduced from previous value
    $textSize = '0.65rem'; // Smaller base text size
    $tableArrangement = $config['table_arrangement'] ?? 'stacked';

    // Get border and background styles
    $borderStyle = $config['default_styles']['border_style'] ?? 'none';
    $borderColor = $config['default_styles']['border_color'] ?? '#e5e7eb';
    $backgroundColor = $config['default_styles']['stripe_color'] ?? '#f9fafb';
    $hoverColor = $config['default_styles']['hover_color'] ?? '#f3f4f6';

    $printConfig = $template->print_config ?? [];

    // In student-info.blade.php
    $containerStyle =
        "
    font-size: " .
        ($printConfig['student_info']['values']['font_size'] ?? '9') .
        "px;
    line-height: " .
        ($printConfig['student_info']['line_height'] ?? '1.1') .
        ";
    margin-bottom: " .
        ($printConfig['student_info']['section_gap'] ?? '6') .
        "px;
";

    $sectionBoxStyle =
        "
    background-color: white;
    border: 1px solid {$borderColor};
    border-radius: 0.2rem;
    padding: " .
        ($printConfig['student_info']['container_padding'] ?? '6') .
        "px;
    margin-bottom: " .
        ($printConfig['student_info']['section_gap'] ?? '6') .
        "px;
";

    // Sort sections by order
    $sections = collect($config['sections'])
        ->sortBy('order')
        ->values()
        ->all();

    // Split sections into rows for side-by-side arrangement
    $rows = collect($sections)->reduce(function ($rows, $section, $index) {
        $rowIndex = floor($index / 2);
        if (!isset($rows[$rowIndex])) {
            $rows[$rowIndex] = [];
        }
        $rows[$rowIndex][] = $section;
        return $rows;
    }, []);


    // Layout specific styles with tighter gaps
    $gridStyle = match ($layout) {
        'single' => 'display: block;',
        'double' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.3rem;',
        'triple' => 'display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.3rem;',
        'grid' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 0.3rem;',
        default => 'display: block;',
    };

@endphp

<div style="{{ $containerStyle }}">
    @if ($tableArrangement === 'stacked')
        <div style="display: flex; flex-direction: column; gap: 0.2rem;">
            @foreach ($sections as $index => $section)
                @if ($section['enabled'] ?? true)
                    <div style="width: 100%;">
                        @include('pdf-report-cards-components.section-content', [
                            'section' => $section,
                            'data' => $data,
                            'sectionKey' => array_key_first($config['sections']),
                            'width' => 'full',
                            'containerStyles' => $sectionBoxStyle,
                        ])
                    </div>
                @endif
            @endforeach
        </div>
    @elseif($tableArrangement === 'side-by-side')
        <div style="display: flex; flex-direction: column; gap: 0.2rem;">
            @foreach ($rows as $row)
                <div
                    style="display: grid; grid-template-columns: repeat({{ count($row) === 1 ? '1' : '2' }}, 1fr); gap: 0.2rem;">
                    @foreach ($row as $section)
                        @php
                            $sectionKey = array_key_first($config['sections']);
                            $isSingle = count($row) === 1;
                        @endphp
                        @if ($section['enabled'] ?? true)
                            <div style="grid-column: span {{ $isSingle ? '1' : '1' }};">
                                @include('pdf-report-cards-components.section-content', [
                                    'section' => $section,
                                    'data' => $data,
                                    'sectionKey' => $sectionKey,
                                    'width' => $isSingle ? '100%' : '50%',
                                    'containerStyles' => $sectionBoxStyle,
                                ])
                            </div>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        {{-- Grid Layout --}}
        <div style="{{ $gridStyle }}">
            @foreach ($sections as $section)
                @if ($section['enabled'] ?? true)
                    @php
                        $width = $section['width'] ?? 'full';
                        $columnSpan = match ($width) {
                            'full' => 'grid-column: 1 / -1;',
                            '2/3' => 'grid-column: span 2;',
                            default => '',
                        };
                    @endphp
                    <div style="{{ $columnSpan }}">
                        @include('pdf-report-cards-components.section-content', [
                            'section' => $section,
                            'data' => $data,
                           
                        ])
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

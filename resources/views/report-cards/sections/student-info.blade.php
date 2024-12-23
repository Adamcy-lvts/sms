{{-- resources/views/report-cards/sections/student-info.blade.php --}}
@php
    $config = $template->getStudentInfoConfig();
    $layout = $config['layout'] ?? 'single';
    $spacing = ($config['spacing'] ?? 4) * 0.25 . 'rem'; // Convert to rem
    $textSize = match ($config['text_size'] ?? 'text-base') {
        'text-sm' => '0.875rem',
        'text-base' => '1rem',
        'text-lg' => '1.125rem',
        'text-xl' => '1.25rem',
        default => '1rem',
    };
    $tableArrangement = $config['table_arrangement'] ?? 'stacked';

    // Get border and background styles
    $borderStyle = $config['default_styles']['border_style'] ?? 'none';
    $borderColor = $config['default_styles']['border_color'] ?? '#e5e7eb';
    $backgroundColor = $config['default_styles']['stripe_color'] ?? '#f9fafb';
    $hoverColor = $config['default_styles']['hover_color'] ?? '#f3f4f6';

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

    // Base container styles
    $containerStyle = "
        font-size: {$textSize}; 
        margin-bottom: {$spacing};
    ";

    // Layout specific styles
    $gridStyle = match ($layout) {
        'single' => 'display: block;',
        'double' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;',
        'triple' => 'display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem;',
        'grid' => 'display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;',
        default => 'display: block;',
    };
@endphp

<div style="{{ $containerStyle }}">
    @if ($tableArrangement === 'stacked')
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach ($sections as $index => $section)
                @if ($section['enabled'] ?? true)
                    <div style="width: 100%;">
                        @include('report-cards.sections.section-content', [
                            'section' => $section,
                            'config' => $config,
                            'data' => $data,
                            'sectionKey' => array_key_first($config['sections']),
                            'width' => 'full',
                            'containerStyles' => "
                                                        background-color: white;
                                                        border: 1px solid {$borderColor};
                                                        border-radius: 0.5rem;
                                                        padding: 1rem;
                                                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                                                        font-size: {$textSize};
                                                    ",
                        ])
                    </div>
                @endif
            @endforeach
        </div>
    @elseif($tableArrangement === 'side-by-side')
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            @foreach ($rows as $row)
                <div
                    style="display: grid; grid-template-columns: repeat({{ count($row) === 1 ? '1' : '2' }}, 1fr); gap: 1rem;">
                    @foreach ($row as $section)
                        @php
                            $sectionKey = array_key_first($config['sections']);
                            $isSingle = count($row) === 1;
                        @endphp
                        @if ($section['enabled'] ?? true)
                            <div style="grid-column: span {{ $isSingle ? '1' : '1' }};">
                                @include('report-cards.sections.section-content', [
                                    'section' => $section,
                                    'config' => $config,
                                    'data' => $data,
                                    'sectionKey' => $sectionKey,
                                    'width' => $isSingle ? '100%' : '50%',
                                    'containerStyles' => "
                                                                        background-color: white;
                                                                        border: 1px solid {$borderColor};
                                                                        border-radius: 0.5rem;
                                                                        padding: 1rem;
                                                                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                                                                        font-size: {$textSize};
                                                                    ",
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
                        @include('report-cards.sections.section-content', [
                            'section' => $section,
                            'config' => $config,
                            'data' => $data,
                            'containerStyles' => "
                                                        background-color: white;
                                                        border: 1px solid {$borderColor};
                                                        border-radius: 0.5rem;
                                                        padding: 1rem;
                                                        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                                                        font-size: {$textSize};
                                                    ",
                        ])
                    </div>
                @endif
            @endforeach
        </div>
    @endif
</div>

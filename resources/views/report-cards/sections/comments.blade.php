{{-- resources/views/report-cards/sections/comments.blade.php
@php
    $commentsConfig = $template->getCommentsConfig();

    // First check if comments section is enabled globally
    if (!($commentsConfig['enabled'] ?? false)) {
        return;
    }

    // Convert spacing to rem values
    $spacingValues = [
        'tight' => '0.5rem', // 8px
        'normal' => '1rem', // 16px
        'relaxed' => '1.5rem', // 24px
    ];
    $spacing = $spacingValues[$commentsConfig['spacing'] ?? 'normal'];

    // Container styles
    $containerStyle = "margin-bottom: {$spacing};";

    // Layout styles
    $layoutStyle = match ($commentsConfig['layout']) {
        'side-by-side' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;',
        'grid' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;',
        default => 'display: flex; flex-direction: column; gap: 1rem;',
    };
@endphp

<div style="{{ $containerStyle }}">
    <div style="{{ $layoutStyle }}">
        @foreach ($commentsConfig['sections'] as $section)
            @php
                // Skip if section is disabled
                if (!($section['enabled'] ?? true)) {
                    continue;
                }

                // Get comment data from database or fall back to template defaults
                $commentKey = Str::slug($section['title']);
                $commentData = $data['comments'][$commentKey] ?? null;

                // Determine the text from database or template
                if ($section['type'] === 'predefined' && !empty($section['predefined_comments'])) {
                    $commentCode = $commentData['comment'] ?? array_key_first($section['predefined_comments']);
                    $commentText =
                        $commentData['comment'] ??
                        ($section['predefined_comments'][$commentCode] ?? 'No comment available.');
                } else {
                    $commentText = $commentData['comment'] ?? ($section['default_comment'] ?? 'No comment provided.');
                }

                // Style configurations
                $fontSize = $section['style']['font_size'] ?? '0.875rem'; // 14px
                $fontWeight = $section['style']['font_weight'] ?? '400';
                $lineHeight = $section['style']['line_height'] ?? '1.5';
                $textColor = $section['style']['text_color'] ?? '#374151';

                // Container styles
                $borderStyle = match ($section['style']['border']) {
                    'rounded' => 'border: 0.0625rem solid #e5e7eb; border-radius: 0.5rem;', // 1px border, 8px radius
                    'simple' => 'border: 0.0625rem solid #e5e7eb;', // 1px border
                    default => '',
                };

                $bgColor = match ($section['style']['background']) {
                    'light' => 'background-color: #f9fafb;',
                    'highlighted' => 'background-color: #eff6ff;',
                    default => 'background-color: #ffffff;',
                };

                $sectionWidth = $commentsConfig['layout'] === 'stacked' ? 'width: 100%;' : '';

                $sectionStyle = "{$borderStyle} {$bgColor} {$sectionWidth} padding: 1rem;"; // 16px padding

                // Text styles
                $commentStyle = "
                    font-size: {$fontSize}; 
                    font-weight: {$fontWeight}; 
                    line-height: {$lineHeight}; 
                    color: {$textColor};
                    margin: 0;
                ";

                // Title styles
                $titleStyle = "
                    font-size: 1rem;
                    font-weight: 500;
                    color: #111827;
                    margin-bottom: 0.5rem;
                ";
            @endphp

            <div style="{{ $sectionStyle }}">
                <h4 style="{{ $titleStyle }}">{{ $section['title'] }}</h4>

                <div style="{{ $commentStyle }}">
                    {!! $commentText !!}
                </div>
             
                @if (($section['show_signatures'] ?? false) && !empty($section['signature_fields']))
                    <div style="margin-top: 1rem;">
                        <div
                            style="{{ $section['signature_layout'] === 'side-by-side' ? 'display: flex; justify-content: space-between; width: 100%;' : 'display: flex; flex-direction: column; gap: 1rem;' }}">
                            @foreach ($section['signature_fields'] as $field)
                                @if ($field['enabled'] ?? true)
                                    @php
                                        $width =
                                            $section['signature_layout'] === 'side-by-side'
                                                ? 'calc(50% - 0.5rem)' // Subtracting half the gap
                                                : $field['width'] ?? '100%';

                                        // Build margin styles from spacing values with rem units
                                        $margins = [];
                                        foreach (['top', 'bottom', 'left', 'right'] as $direction) {
                                            $value = $field['spacing']['margin_' . $direction] ?? '0';
                                            $rems = match ($value) {
                                                '0' => '0',
                                                '1' => '0.25rem', // 4px
                                                '2' => '0.5rem', // 8px
                                                '3' => '1rem', // 16px
                                                '4' => '1.5rem', // 24px
                                                '5' => '2rem', // 32px
                                                '6' => '3rem', // 48px
                                                default => '0',
                                            };
                                            $margins[] = "margin-{$direction}: {$rems}";
                                        }
                                        $fieldStyle =
                                            "
                                            width: {$width}; 
                                            " .
                                            implode('; ', $margins) .
                                            ";
                                            display: flex;
                                            flex-direction: column;
                                        ";
                                    @endphp

                                    <div style="{{ $fieldStyle }}">
                                        @if ($field['type'] === 'signature' || $field['type'] === 'date' || $field['type'] === 'name')
                                            <div style="border-bottom: 0.0625rem solid #e5e7eb; width: 100%;"></div>
                                        @endif
                                        <span
                                            style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">{{ $field['label'] }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div> --}}

{{-- resources/views/report-cards/sections/comments.blade.php --}}
{{-- resources/views/report-cards/sections/comments.blade.php --}}
{{-- resources/views/report-cards/sections/comments.blade.php --}}
@php
    $commentsConfig = $template->getCommentsConfig();

    // First check if comments section is enabled globally
    if (!($commentsConfig['enabled'] ?? false)) {
        return;
    }

    $spacingValues = [
        'tight' => '0.5rem',
        'normal' => '1rem',
        'relaxed' => '1.5rem',
    ];
    $spacing = $spacingValues[$commentsConfig['spacing'] ?? 'normal'];

    $containerStyle = "margin-bottom: {$spacing};";
    $layoutStyle = match ($commentsConfig['layout'] ?? 'stacked') {
        'side-by-side' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;',
        'grid' => 'display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;',
        default => 'display: flex; flex-direction: column; gap: 1rem;',
    };
@endphp

<div style="{{ $containerStyle }}">
    <div style="{{ $layoutStyle }}">
        @foreach ($commentsConfig['sections'] as $section)
            @if (!empty($section['enabled']))
                @php
                    $isTeacher = str_contains(strtolower($section['title'] ?? ''), 'teacher');
                    $commentKey = $isTeacher ? 'class_teacher' : 'principal';
                    $commentData = $data['comments'][$commentKey] ?? [];

                    $sectionStyle =
                        "
                        border: 0.0625rem solid #e5e7eb;
                        border-radius: 0.5rem;
                        background-color: " .
                        (!empty($section['style']['background']) && $section['style']['background'] === 'light'
                            ? '#f9fafb'
                            : '#ffffff') .
                        ";
                        padding: 1rem;
                        " .
                        ($commentsConfig['layout'] === 'stacked' ? 'width: 100%;' : '');

                    $commentStyle =
                        "
                        font-size: " .
                        (!empty($section['style']['font_size']) ? $section['style']['font_size'] : '0.875rem') .
                        ";
                        line-height: " .
                        (!empty($section['style']['line_height']) ? $section['style']['line_height'] : '1.5') .
                        ";
                        color: #374151;
                        margin: 0;
                    ";

                    $titleStyle = "
                        font-size: 1rem;
                        font-weight: 500;
                        color: #111827;
                        margin-bottom: 0.5rem;
                    ";
                @endphp

                <div style="{{ $sectionStyle }}">
                    <h4 style="{{ $titleStyle }}">
                        {{ $section['title'] ?? ($isTeacher ? "Class Teacher's Comment" : "Principal's Comment") }}</h4>

                    <div style="{{ $commentStyle }}">
                        {{ $commentData['comment'] ?? 'No comment provided.' }}
                    </div>

                    @if (($section['show_signatures'] ?? false) && !empty($section['signature_fields']))
                        <div style="margin-top: 1rem;">
                            @php
                                $hasDigitalSignature = !empty($commentData['digital_signature']['signature_url']);
                                $showDigital =
                                    !empty($section['signature_fields']['show_digital']) && $hasDigitalSignature;
                                $showManual = !empty($section['signature_fields']['show_manual']);

                                $alignment = match ($section['signature_fields']['alignment'] ?? 'right') {
                                    'left' => 'flex-start',
                                    'center' => 'center',
                                    default => 'flex-end',
                                };
                            @endphp

                            <div
                                style="display: flex; justify-content: {{ $alignment }}; align-items: flex-end; gap: 1rem;">
                                @if ($showDigital)
                                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                        <img src="{{ $commentData['digital_signature']['signature_url'] }}"
                                            alt="Digital Signature" style="height: 3rem; object-fit: contain;">

                                        @if (!empty($section['signature_fields']['show_name']))
                                            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                                {{ $commentData['digital_signature']['name'] ?? '' }}
                                            </div>
                                        @endif

                                        @if (!empty($section['signature_fields']['show_date']))
                                            <div style="font-size: 0.75rem; color: #6b7280;">
                                                {{ $commentData['digital_signature']['date'] ?? '' }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($showManual)
                                    <div style="display: flex; width: 100%; margin-top: 2rem;">
                                        <div style="flex: 3; padding-right: 2rem;">
                                            <div style="border-bottom: 0.0625rem solid #e5e7eb; width: 100%;"></div>
                                            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                                {{ $isTeacher ? "Class Teacher's" : "Principal's" }} Signature
                                            </div>
                                        </div>

                                        @if (!empty($section['signature_fields']['show_date']))
                                            <div style="flex: 1;">
                                                <div style="border-bottom: 0.0625rem solid #e5e7eb; width: 100%;"></div>
                                                <div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">
                                                    Date
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</div>

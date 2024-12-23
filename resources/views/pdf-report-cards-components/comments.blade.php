{{-- resources/views/pdf-report-cards-components/comments.blade.php
@php
    $commentsConfig = $template->getCommentsConfig();

    // First check if comments section is enabled globally
    if (!($commentsConfig['enabled'] ?? false)) {
        return;
    }

    // Container styles
    $containerStyle = "
        margin-bottom: 0.2rem;
        font-size: 0.65rem;
        line-height: 1.1;
    ";

    // Layout styles
    $layoutStyle = match ($commentsConfig['layout']) {
        'side-by-side' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;',
        'grid' => 'display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem;',
        default => 'display: flex; flex-direction: column; gap: 0.2rem;', // stacked
    };

    // Common text styles
    $titleStyle = "
        font-size: 0.7rem;
        font-weight: 500;
        color: #111827;
       
    ";

    $commentTextStyle = "
        font-size: 0.65rem;
        line-height: 1.2;
        color: #374151;
        margin-bottom: 1.3rem;
    ";

    // Signature styles
    $signatureContainerStyle = "
        margin-top: 0.2rem;
        padding-top: 0.2rem;
     
    ";

    $signatureGridStyle = "
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
    ";

    $signatureLabelStyle = "
        font-size: 0.6rem;
        color: #6B7280;
      
    ";

    $signatureLineStyle = "
        border-bottom: 1px solid #9CA3AF;
        width: 100%;
        margin-bottom: 0.5rem;
    ";
@endphp

<div style="{{ $containerStyle }}" class="bg-gray-900">
    <div style="{{ $layoutStyle }}">
        @foreach ($commentsConfig['sections'] as $section)
            @php
                if (!($section['enabled'] ?? true)) {
                    continue;
                }

                $commentKey = Str::slug($section['title']);
                $commentData = $data['comments'][$commentKey] ?? null;

                if ($section['type'] === 'predefined' && !empty($section['predefined_comments'])) {
                    $commentCode = $commentData['comment'] ?? array_key_first($section['predefined_comments']);
                    $commentText = $section['predefined_comments'][$commentCode] ?? 'No comment available.';
                } else {
                    $commentText = $commentData['comment'] ?? ($section['default_comment'] ?? 'No comment provided.');
                }

                // Section box style
                $sectionStyle =
                    "
                    background-color: " .
                    ($section['style']['background'] === 'light' ? '#f9fafb' : 'white') .
                    ";
                    border: 1px solid #e5e7eb;
                    border-radius: 0.2rem;
                    padding: 0.5rem;
                ";
            @endphp

            <div style="{{ $sectionStyle }}" class="dark:bg-gray-800">
                <h4 style="{{ $titleStyle }} margin:0rem 0rem 0.5rem 0rem;">{{ $section['title'] }}</h4>

                <div style="{{ $commentTextStyle }}">
                    {!! nl2br(e($commentText)) !!}
                </div>

                @if (($section['show_signatures'] ?? false) && !empty($section['signature_fields']))
                    <div style="{{ $signatureContainerStyle }}">
                        <div style="{{ $section['signature_layout'] === 'side-by-side' ? $signatureGridStyle : '' }}">
                            @foreach ($section['signature_fields'] as $field)
                                @if ($field['enabled'] ?? true)
                                    @php
                                        $fieldWidth =
                                            $section['signature_layout'] === 'side-by-side'
                                                ? '100%'
                                                : $field['width'] ?? '100%';

                                        $fieldStyle = "
                                            width: {$fieldWidth};
                                            margin: 0.1rem 0;
                                        ";
                                    @endphp

                                    <div style="{{ $fieldStyle }}">
                                        @if (in_array($field['type'], ['signature', 'date', 'name']))
                                            <div style="{{ $signatureLineStyle }}"></div>
                                        @endif
                                        <span style="{{ $signatureLabelStyle }}">{{ $field['label'] }}</span>
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

@php
    $commentsConfig = $template->getCommentsConfig();
    $printConfig = $template->print_config['comments'] ?? [];
    // First check if comments section is enabled globally
    if (!($commentsConfig['enabled'] ?? false)) {
        return;
    }

    $spacing = $printConfig['section_spacing'] ?? '8';

    $signatureHeight = $printConfig['signature_height'] ?? '32';

    $containerStyle = "margin-bottom: {$spacing}px;";
    $layoutStyle = match ($commentsConfig['layout'] ?? 'stacked') {
        'side-by-side' => 'display: grid; grid-template-columns: 1fr 1fr; gap: ' .
            ($printConfig['section_gap'] ?? '12') .
            'px;',
        'grid' => 'display: grid; grid-template-columns: 1fr 1fr; gap: ' .
            ($printConfig['section_gap'] ?? '12') .
            'px;',
        default => 'display: flex; flex-direction: column; gap: ' . ($printConfig['section_gap'] ?? '12') . 'px;',
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
   padding: " .
                        ($printConfig['container_padding'] ?? '12') .
                        "px;
   " .
                        ($commentsConfig['layout'] === 'stacked' ? 'width: 100%;' : '');

                    $titleStyle =
                        "
   font-size: " .
                        ($printConfig['title']['font_size'] ?? '12') .
                        "px;
   font-weight: 500;
   color: #111827;
   margin: " .
                        ($printConfig['title_margin'] ?? '8') .
                        "px 0;
";

                    $commentStyle =
                        "
    font-size: " .
                        ($printConfig['content']['font_size'] ?? '10') .
                        "px;
    line-height: " .
                        ($printConfig['line_height'] ?? '1.2') .
                        ";
    color: #374151;
    margin: 0;
";
                @endphp

                <div style="{{ $sectionStyle }}">
                    <h4 style="{{ $titleStyle }}">
                        {{ $section['title'] ?? ($isTeacher ? "Class Teacher's Comment" : "Principal's Comment") }}</h4>

                    <div style="{{ $commentStyle }}">
                        {{ $commentData['comment'] ?? 'No comment provided.' }}
                    </div>

                    @if (($section['show_signatures'] ?? false) && !empty($section['signature_fields']))
                        <div style="margin-top: 0.5rem;">
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
                                style="display: flex; justify-content: {{ $alignment }}; align-items: flex-end; gap: 0.5rem;">
                                @if ($showDigital)
                                    <div style="display: flex; flex-direction: column; align-items: flex-end;">
                                        <img src="{{ $signatures[$commentKey] }}" alt="Digital Signature"
                                            style="height: {{ $signatureHeight }}px; object-fit: contain;">

                                        @if (!empty($section['signature_fields']['show_name']))
                                            <div style="font-size: 0.6rem; color: #6b7280; margin-top: 0.25rem;">
                                                {{ $commentData['digital_signature']['name'] ?? '' }}
                                            </div>
                                        @endif

                                        @if (!empty($section['signature_fields']['show_date']))
                                            <div style="font-size: 0.6rem; color: #6b7280;">
                                                {{ $commentData['digital_signature']['date'] ?? '' }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if ($showManual)
                                    <div style="display: flex; width: 100%; margin-top: 2rem;">
                                        <div style="flex: 3; padding-right: 2rem;">
                                            <div style="border-bottom: 0.06rem solid #e5e7eb; width: 100%;"></div>
                                            <div style="font-size: 0.6rem; color: #30333a; margin-top: 0.25rem;">
                                                {{ $isTeacher ? "Class Teacher's" : "Principal's" }} Signature
                                            </div>
                                        </div>

                                        @if (!empty($section['signature_fields']['show_date']))
                                            <div style="flex: 1;">
                                                <div style="border-bottom: 0.06rem solid #e5e7eb; width: 100%;"></div>
                                                <div style="font-size: 0.6rem; color: #30333a; margin-top: 0.25rem;">
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
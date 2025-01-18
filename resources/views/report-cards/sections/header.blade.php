
{{-- resources/views/report-cards/sections/header.blade.php --}}
@php

    $headerConfig = $headerConfig ?? [];
    $customStyles = $headerConfig['custom_styles'] ?? [];
    $academicInfo = $headerConfig['academic_info'] ?? [];
    $academicStyles = $academicInfo['styles'] ?? [];

    $rtlConfig = $template->getRtlConfig();
    $direction = $rtlConfig['text_direction'] ?? 'ltr';

    // Get session and term info with fallbacks
    $sessionName = $data['academic_info']['session']['name'] ?? '';
    $termName = $data['academic_info']['term']['name'] ?? '';
    // Define layoutType with proper fallback
    $layoutType = $headerConfig['logo_position'] ?? 'center';

    // Enhanced logo styles with more configuration options
    $logoHeight = $headerConfig['logo_height'] ?? '120px'; // Increased default size
    $logoWidth = $headerConfig['logo_width'] ?? 'auto';
    $logoStyle = "
        height: {$logoHeight}; 
        width: {$logoWidth}; 
        object-fit: contain;
        max-width: 100%;
    ";

    // Add spacing configuration
    $spacing = [
        'gap' => $headerConfig['spacing']['gap'] ?? '2rem',
        'margin_top' => $headerConfig['spacing']['margin_top'] ?? '3rem',
        'margin_bottom' => $headerConfig['spacing']['margin_bottom'] ?? '0.7rem',
    ];

    // Update the container styles to handle vertical centering
    $pageContainerStyle =
        "
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center; /* Add this for vertical centering */
    min-height: " .
        ($layoutType === 'center-vertical' ? '30vh' : 'auto') .
        "; 
    margin-top: " .
        ($layoutType === 'center-vertical' ? '0' : ($layoutType === 'center' ? '3rem' : '2rem')) .
        ";
    margin-bottom: " .
        ($layoutType === 'center-vertical' ? '0' : ($layoutType === 'center' ? '0.7rem' : '0.5rem')) .
        ";
";

    $headerContainerStyle =
        "
    width: 50rem; 
    display: flex;
    align-items: " .
        ($layoutType === 'center' || $layoutType === 'center-vertical' ? 'center' : 'flex-start') .
        ";
    gap: " .
        ($layoutType === 'center' || $layoutType === 'center-vertical' ? '1rem' : '2rem') .
        ";
    " .
        match ($layoutType) {
            'left' => '',
            'right' => 'flex-direction: row-reverse;',
            'center-vertical' => 'flex-direction: column; align-items: center;',
            default => 'flex-direction: column; align-items: center;',
        };

    $textContainerStyle = match ($layoutType) {
        'left' => 'text-align: left; flex: 1;',
        'right' => 'text-align: right; flex: 1;',
        default => 'text-align: center; width: 100%;',
    };

    $logoContainerStyle =
        "
        display: flex;
        align-items: center;
        " .
        ($layoutType === 'center');


    // Adjust container spacing based on layout
    $containerStyle =
        "
        width: 100%;
        display: flex; 
        justify-content: center;
        margin-top: " .
        ($layoutType === 'center' ? '3rem' : '2rem') .
        ";
        margin-bottom: " .
        ($layoutType === 'center' ? '2rem' : '1.5rem') .
        ";
    ";

@endphp

{{-- Centered/Stacked Layout --}}
@if ($layoutType === 'center')
    <div style="width: 100%; display: flex; justify-content: center; margin-top: 3rem; margin-bottom: 0.7rem;">
        <div style="width: 50rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
            @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                <div style="{{ $logoContainerStyle }}">
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }} Logo"
                        style="{{ $logoStyle }}" class="dark:brightness-90">
                </div>
            @endif
            <div style="text-align: center; width: 100%;">
                @include('report-cards.partials.header-content')
            </div>
        </div>
    </div>

    {{-- Center-vertical with left-aligned content layout --}}
    {{-- Center-vertical with centered content layout --}}
@elseif($layoutType === 'center-vertical')
    <div style="width: 100%; min-height: 20vh; display: flex; justify-content: center; align-items: center;">
        <div style="width: 50rem; height: 100%; display: flex; justify-content: center; align-items: center;">
            <div style="display: flex; flex-direction: row; align-items: center; gap: 2rem;">
                @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                    <div style="flex-shrink: 0;">
                        <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }} Logo"
                            style="{{ $logoStyle }}" class="dark:brightness-90">
                    </div>
                @endif
                <div style="flex: 1; text-align: left;">
                    @include('report-cards.partials.header-content')
                </div>
            </div>
        </div>
    </div>

    {{-- Left-Aligned Layout --}}
@elseif($layoutType === 'left')
    <div style="{{ $pageContainerStyle }}">
        <div style="{{ $headerContainerStyle }}">
            @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                <div style="flex-shrink: 0;">
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }} Logo"
                        style="{{ $logoStyle }}" class="dark:brightness-90">
                </div>
            @endif

            <div style="{{ $textContainerStyle }}">
                @include('report-cards.partials.header-content')
            </div>
        </div>
    </div>

    {{-- Right-Aligned Layout --}}
@elseif($layoutType === 'right')
    <div style="width: 100%; display: flex; justify-content: center; margin-top: 3rem; margin-bottom: 0.7rem;">
        <div style="width: 50rem; display: flex; flex-direction: row-reverse; align-items: flex-start; gap: 2rem;">
            @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                <div style="flex-shrink: 0;">
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }} Logo"
                        style="{{ $logoStyle }}" class="dark:brightness-90">
                </div>
            @endif

            <div style="flex: 1; text-align: right;">
                @include('report-cards.partials.header-content')
            </div>
        </div>
    </div>
@endif

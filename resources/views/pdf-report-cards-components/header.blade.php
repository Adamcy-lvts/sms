{{-- resources/views/pdf-report-cards-components/header.blade.php --}}
@php

    $headerConfig = $headerConfig ?? [];
    $customStyles = $headerConfig['custom_styles'] ?? [];
    $academicInfo = $headerConfig['academic_info'] ?? [];

    $academicStyles = $academicInfo['styles'] ?? [];

    // Get print configuration with defaults
    $printConfig = $template->print_config['header'] ?? [];

    $rtlConfig = $template->getRtlConfig();
    $direction = $rtlConfig['text_direction'] ?? 'ltr';

    // Get session and term info with fallbacks
    $sessionName = $data['academic_info']['session']['name'] ?? '';
    $termName = $data['academic_info']['term']['name'] ?? '';

    $layoutType = $headerConfig['logo_position'] ?? 'center';

    // dd($layoutType);

    // Section spacing from config with default
    $sectionSpacing = $printConfig['spacing'] ?? '8';

    // Logo height from config with default
    $logoHeight = $printConfig['logo_height'] ?? '80';
// dd($logoHeight);
    if ($layoutType === 'center-vertical') {
        $logoHeight = '85';
    }
    $textTransform = $headerConfig['typography']['school_name']['text_case'] ?? 'none';
    // Font sizes from config with defaults
    $schoolNameFontSize = $printConfig['school_name']['font_size'] ?? '14';
    // dd($schoolNameFontSize);
    $addressFontSize = $printConfig['address']['font_size'] ?? '11';
    $contactInfoFontSize = $printConfig['contact_info']['font_size'] ?? '10';
    $reportTitleFontSize = $printConfig['report_title']['font_size'] ?? '12';

    // Keep existing layout styles but update font sizes and spacing
    $schoolNameStyle = "
        font-size: {$schoolNameFontSize}px;
        font-weight: 700;
        margin-bottom: {$sectionSpacing}px;
        margin-top: {$sectionSpacing}px;
        line-height: 1.1;
    ";

    $addressStyle = "
        color: #4B5563;
        margin-top: {$sectionSpacing}px;
        margin-bottom: {$sectionSpacing}px;
        font-size: {$addressFontSize}px;
        font-weight: 600;
        line-height: 1.1;
    ";

    $contactInfoStyle =
        "
        font-size: {$contactInfoFontSize}px;
        line-height: 1.1;
        font-weight: 600;
        margin-bottom: {$sectionSpacing}px;
        " .
        (($headerConfig['contact_info']['layout'] ?? 'inline') === 'inline'
            ? 'display: flex; align-items: center; gap: ' . $sectionSpacing / 2 . 'px; justify-content: center;'
            : 'display: flex; flex-direction: column; gap: ' . $sectionSpacing / 4 . 'px;');

    $labelStyle =
        "
        color: #4B5563; 
        margin-right: " .
        $sectionSpacing / 4 .
        "px; 
        font-size: {$contactInfoFontSize}px;
    ";

    $titleStyles = [
        'font-size' => $reportTitleFontSize . 'px',
        'font-weight' => '600',
        'color' => '#111827',
        'margin-top' => $sectionSpacing . 'px',
        'margin-bottom' => $sectionSpacing . 'px',
  
        'letter-spacing' => '0.05em',
        'text-transform' => 'uppercase',
    ];

    // // Logo container styles
    $logoContainerStyle =
        "
        display: flex;
        margin-bottom: 0px;
        line-height:1;
        align-items: center;
        max-height: {$logoHeight}px;
    " . ($layoutType === 'center' ? ' justify-content: center;' : '');

    // Keep other existing container styles
    $pageContainerStyle = "
        width: 100%;
        display: flex;
        justify-content: center;
        margin-bottom: {$sectionSpacing}px;
       
    ";

    $headerContainerStyle =
        "
        width: 100%; 
        display: flex;
        align-items: center;
        gap: " .
        $sectionSpacing / 2 .
        "px;
        " .
        match ($layoutType) {
            'left' => '',
            'right' => 'flex-direction: row-reverse;',
            default => 'flex-direction: column; align-items: center;',
        };

    $textContainerStyle = match ($layoutType) {
        'left' => 'text-align: left; flex: 1;',
        'right' => 'text-align: right; flex: 1;',
        default => 'text-align: center; width: 100%;',
    };

    $contactItemStyle =
        ($headerConfig['contact_info']['layout'] ?? 'inline') === 'inline'
            ? 'display: inline-flex; align-items: center;'
            : 'display: block;';

    $titleInlineStyles = collect($titleStyles)->map(fn($value, $prop) => "{$prop}: {$value};")->implode(' ');
  
@endphp

<div style="{{ $pageContainerStyle }}">
    <div style="{{ $headerContainerStyle }}">

        {{-- Centered/Stacked Layout --}}
        @if ($layoutType === 'center')
            <div style="width: 100%; display: flex; justify-content: center; margin-bottom: 0.2rem;">
                <div style="width: 50rem; display: flex; flex-direction: column; align-items: center; gap: 0.5rem;">
                    @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                        <div style="{{ $logoContainerStyle }}">
                            <img src="{{ $schoolLogo }}" alt="{{ $school->name }} Logo"
                                style="height: {{ $logoHeight }}px; width: auto; object-fit: contain;">
                        </div>
                    @endif
                    <div style="text-align: center; width: 100%;">
                        @include('pdf-report-cards-components.header-content')
                    </div>
                </div>
            </div>

            {{-- Center-vertical with centered content layout --}}
        @elseif($layoutType === 'center-vertical')
            <div style="width: 100%; display: flex; justify-content: center; align-items: center;">
                <div style="width: 50rem; height: 100%; display: flex; justify-content: center; align-items: center;">
                    <div style="display: flex; flex-direction: row; align-items: center; gap: 1rem;">
                        @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                            <div style="flex-shrink: 0;">
                                <img src="{{ $schoolLogo }}" alt="{{ $school->name }} Logo"
                                    style="height: {{ $logoHeight }}px; width: auto; object-fit: contain;">
                            </div>
                        @endif
                        <div style="flex: 1; text-align: left;">
                            @include('pdf-report-cards-components.header-content')
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
                            <img src="{{ $schoolLogo }}" alt="{{ $school->name }} Logo"
                                style="height: {{ $logoHeight }}px; width: auto; object-fit: contain;">
                        </div>
                    @endif

                    <div style="{{ $textContainerStyle }}">
                        @include('pdf-report-cards-components.header-content')
                    </div>
                </div>
            </div>

            {{-- Right-Aligned Layout --}}
        @elseif($layoutType === 'right')
            <div style="width: 100%; display: flex; justify-content: center; margin-top: 3rem; margin-bottom: 0.7rem;">
                <div
                    style="width: 50rem; display: flex; flex-direction: row-reverse; align-items: flex-start; gap: 1rem;">
                    @if (($headerConfig['show_logo'] ?? false) && $school->logo)
                        <div style="flex-shrink: 0;">
                            <img src="{{ $schoolLogo }}" alt="{{ $school->name }} Logo"
                                style="height: {{ $logoHeight }}px; width: auto; object-fit: contain;">
                        </div>
                    @endif

                    <div style="flex: 1; text-align: right;">
                        @include('pdf-report-cards-components.header-content')
                    </div>
                </div>
            </div>
        @endif



    </div>
</div>

{{-- resources/views/report-cards/sections/header.blade.php --}}
{{-- @php
    // Keeping all existing PHP variables and calculations exactly as they are
    $headerConfig = $headerConfig ?? [];
    $customStyles = $headerConfig['custom_styles'] ?? [];
    $academicInfo = $headerConfig['academic_info'] ?? [];
    $academicStyles = $academicInfo['styles'] ?? [];

    $rtlConfig = $template->getRtlConfig();
    $direction = $rtlConfig['text_direction'] ?? 'ltr';

    // Get session and term info with fallbacks
    $sessionName = $data['academic_info']['session']['name'] ?? '';
    $termName = $data['academic_info']['term']['name'] ?? '';

    $logoPosition = $headerConfig['logo_position'] ?? 'center';

    // Keeping all the existing style definitions
    $pageContainerStyle = "
        width: 100%;
        display: flex;
        justify-content: center;
        margin-top:3rem;
        margin-bottom: 0.7rem;
    ";

    $headerContainerStyle =
        "
        width: 50rem; 
        display: flex;
        align-items: center;
        gap: 0.5rem;
        " .
        match ($logoPosition) {
            'left' => '',
            'right' => 'flex-direction: row-reverse;',
            default => 'flex-direction: column; align-items: center;',
        };

    $textContainerStyle = match ($logoPosition) {
        'left' => 'text-align: left; flex: 1;',
        'right' => 'text-align: right; flex: 1;',
        default => 'text-align: center; width: 100%;',
    };

    $logoContainerStyle =
        "
        display: flex;
        align-items: center;
        " .
        ($logoPosition === 'center');

    $schoolNameSize = $customStyles['school_name_size'] ?? '1.5rem';
    $schoolNameStyle = "
        font-size: {$schoolNameSize}; 
        font-weight: 700;
        margin-bottom: 0.5rem;
    ";

    $addressStyle = "
        color: #4B5563; 
        margin-bottom: 0.25rem;
    ";

    $contactInfoStyle =
        "
        font-size: " .
        ($customStyles['contact_info_size'] ?? '0.875rem') .
        ";
        " .
        (($headerConfig['contact_info']['layout'] ?? 'inline') === 'inline'
            ? 'display: flex; align-items: center; gap: 1rem; justify-content: center;'
            : 'display: flex; flex-direction: column; gap: 0.25rem;');

    $contactItemStyle =
        ($headerConfig['contact_info']['layout'] ?? 'inline') === 'inline'
            ? 'display: inline-flex; align-items: center;'
            : 'display: block;';

    $labelStyle = 'color: #4B5563; margin-right: 0.25rem;';
@endphp

<div style="{{ $pageContainerStyle }}">
    <div style="{{ $headerContainerStyle }}">
        @if (($headerConfig['show_logo'] ?? false) && $school->logo_url)
            <div style="{{ $logoContainerStyle }}">
                <img src="{{ asset('storage/' . $school->logo) }}" alt="{{ $school->name }} Logo"
                    style="height: {{ $headerConfig['logo_height'] ?? '5rem' }}; width: auto; object-fit: contain;"
                    class="dark:brightness-90">
            </div>
        @endif

        <div style="{{ $textContainerStyle }}">
            @if ($rtlConfig['header']['show_arabic_name'] && !empty($rtlConfig['header']['arabic_name']))
                <div class="{{ $rtlConfig['header']['arabic_text_size'] }} font-bold dark:text-gray-200"
                    style="font-family: {{ $rtlConfig['arabic_font'] }}; direction: rtl;">
                    {{ $rtlConfig['header']['arabic_name'] }}
                </div>
            @endif
            @if ($headerConfig['show_school_name'] ?? true)
                <h1 style="{{ $schoolNameStyle }}" class="dark:text-gray-200">
                    {{ $school->name }}
                </h1>
                @if ($headerConfig['show_school_address'] ?? true)
                    <p style="{{ $addressStyle }}" class="dark:text-gray-200">{{ $school->address }}</p>
                @endif
            @endif

            @if ($headerConfig['show_contact_information'] ?? true)
                <div style="{{ $contactInfoStyle }}" class="text-gray-600 dark:text-gray-400">
                    @if (($headerConfig['contact_info']['show_phone'] ?? true) && $school->phone)
                        <div style="{{ $contactItemStyle }}">
                            <span style="{{ $labelStyle }}" class="text-gray-600 dark:text-gray-400">
                                {{ $headerConfig['contact_info']['phone_label'] ?? 'Phone Number:' }}
                            </span>
                            <span class="text-gray-800 dark:text-gray-200">{{ $school->phone }}</span>
                        </div>
                    @endif

                    @if (($headerConfig['contact_info']['show_email'] ?? true) && $school->email)
                        <div style="{{ $contactItemStyle }}">
                            <span style="{{ $labelStyle }}" class="text-gray-600 dark:text-gray-400">
                                {{ $headerConfig['contact_info']['email_label'] ?? 'Email:' }}
                            </span>
                            <span class="text-gray-800 dark:text-gray-200">{{ $school->email }}</span>
                        </div>
                    @endif
                </div>
            @endif

            @if ($headerConfig['show_report_title'] ?? true)
                @php
                    $titleStyles = [
                        'font-size' => match ($headerConfig['report_title_size'] ?? 'text-xl') {
                            'text-lg' => '0.875rem',
                            'text-xl' => '1rem',
                            'text-2xl' => '1.25rem',
                            default => '1rem',
                        },
                        'font-weight' => '600',
                        'color' => '#111827',
                        'margin-top' => '0.75rem',
                        'margin-bottom' => '0.5rem',
                        'text-align' => 'center',
                        'letter-spacing' => '0.05em',
                        'text-transform' => 'uppercase',
                    ];

                    $titleInlineStyles = collect($titleStyles)
                        ->map(fn($value, $prop) => "{$prop}: {$value};")
                        ->implode(' ');
                @endphp

                <h2 style="{{ $titleInlineStyles }} " class="text-gray-900 dark:text-white">
                    {{ $termName }} {{ $headerConfig['report_title'] ?? 'TERM REPORT CARD' }}
                </h2>
            @endif

            @if (($academicInfo['show_session'] ?? true) || ($academicInfo['show_term'] ?? true))
                <p
                    class="{{ $academicStyles['size'] ?? 'text-base' }} 
                    {{ $academicStyles['weight'] ?? 'font-normal' }}
                    {{ $academicStyles['color'] ?? 'text-gray-600' }}
                    dark:text-gray-300">
                    {{ $academicInfo['format']['prefix'] ?? '' }}
                    @if ($academicInfo['show_session'] ?? true)
                        {{ $sessionName }}
                    @endif
                    @if (($academicInfo['show_session'] ?? true) && ($academicInfo['show_term'] ?? true))
                        {{ $academicInfo['format']['separator'] ?? ' - ' }}
                    @endif
                    @if ($academicInfo['show_term'] ?? true)
                        {{ $termName }}
                    @endif
                    {{ $academicInfo['format']['suffix'] ?? '' }}
                </p>
            @endif
        </div>
    </div>
</div> --}}
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

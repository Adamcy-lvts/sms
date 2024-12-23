{{-- resources/views/report-cards/partials/header-content.blade.php --}}
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

    $typography = [
        'school_name' => [
            'font_size' => $headerConfig['typography']['school_name']['font_size'] ?? '1.5rem',
            'line_height' => $headerConfig['typography']['school_name']['line_height'] ?? '1.2',
            'margin_bottom' => $headerConfig['typography']['school_name']['margin_bottom'] ?? '0.5rem',
            'font_weight' => $headerConfig['typography']['school_name']['weight'] ?? '700',
            'text_case' => $headerConfig['typography']['school_name']['text_case'] ?? 'none', // Default case
        ],
        'address' => [
            'font_size' => $headerConfig['typography']['address']['font_size'] ?? '0.875rem',
            'line_height' => $headerConfig['typography']['address']['line_height'] ?? '1.4',
            'margin_bottom' => $headerConfig['typography']['address']['margin_bottom'] ?? '0.5rem',
        ],
        'contact' => [
            'font_size' => $headerConfig['typography']['contact']['font_size'] ?? '0.875rem',
            'line_height' => $headerConfig['typography']['contact']['line_height'] ?? '1.4',
            'margin_bottom' => $headerConfig['typography']['contact']['margin_bottom'] ?? '0.5rem',
            'gap' => $headerConfig['typography']['contact']['gap'] ?? '0.75rem',
        ],
        'report_t' => [
            'font_size' => $headerConfig['typography']['report_title']['font_size'] ?? '1.25rem',
            'line_height' => $headerConfig['typography']['report_title']['line_height'] ?? '1.2',
            'margin' => $headerConfig['typography']['report_title']['margin'] ?? '0.75rem',
            'font_weight' => '600',
        ],
    ];

    // Build composite styles
    $schoolNameStyle = "
    font-size: {$typography['school_name']['font_size']};
    line-height: {$typography['school_name']['line_height']};
    margin-bottom: {$typography['school_name']['margin_bottom']};
    font-weight: {$typography['school_name']['font_weight']};
    text-transform: {$typography['school_name']['text_case']};
";

    $addressStyle = "
    font-size: {$typography['address']['font_size']};
    line-height: {$typography['address']['line_height']};
    margin-bottom: {$typography['address']['margin_bottom']};
    color: #4B5563;
";

    $contactInfoStyle =
        "
    font-size: {$typography['contact']['font_size']};
    line-height: {$typography['contact']['line_height']};
    margin-bottom: {$typography['contact']['margin_bottom']};
    display: flex;
    align-items: center;
    gap: {$typography['contact']['gap']}px;
    justify-content: " .
        ($logoPosition === 'center' ? 'center;' : 'flex-start;') .
        "
    ";

    $reportTitleStyle = "
    font-size: {$typography['report_t']['font_size']};
    line-height: {$typography['report_t']['line_height']};
    margin-top: {$typography['report_t']['margin']};
    margin-bottom: {$typography['report_t']['margin']};
    font-weight: {$typography['report_t']['font_weight']};
    letter-spacing: 0.05em;
    text-transform: uppercase;
    color: #111827;
";

    $contactItemStyle =
        ($headerConfig['contact_info']['layout'] ?? 'inline') === 'inline'
            ? 'display: inline-flex; align-items: center;'
            : 'display: block;';

    $labelStyle = 'color: #4B5563; margin-right: 0.25rem;';
@endphp
{{-- Arabic Name --}}
@if ($rtlConfig['header']['show_arabic_name'] && !empty($rtlConfig['header']['arabic_name']))
    <div class="{{ $rtlConfig['header']['arabic_text_size'] }} font-bold dark:text-gray-200"
        style="font-family: {{ $rtlConfig['arabic_font'] }};{{ $schoolNameStyle }}; direction: rtl;">
        {{ $rtlConfig['header']['arabic_name'] }}
    </div>
@endif

{{-- School Name and Address --}}
@if ($headerConfig['show_school_name'] ?? true)
    <h1 style="{{ $schoolNameStyle }};" class="dark:text-gray-200">
        {{ $school->name }}
    </h1>
    @if ($headerConfig['show_school_address'] ?? true)
        <p style="{{ $addressStyle }}" class="dark:text-gray-200">
            {{ $school->address }}
        </p>
    @endif
@endif

{{-- Contact Information --}}
@if ($headerConfig['show_school_contact'] ?? true)
    <div style="{{ $contactInfoStyle }}" class="text-gray-600 dark:text-gray-400">
        @if (($headerConfig['contact_info']['show_phone'] ?? true) && $school->phone)
            <div style="">
                <span style="color: #4B5563; margin-right: 0.25rem;" class="text-gray-600 dark:text-gray-400">
                    {{ $headerConfig['contact_info']['phone_label'] ?? 'Phone:' }}
                </span>
                <span class="text-gray-800 dark:text-gray-200">{{ $school->phone }}</span>
            </div>
        @endif

        @if (($headerConfig['contact_info']['show_email'] ?? true) && $school->email)
            <div style="">
                <span style="color: #4B5563; margin-right: 0.25rem;" class="text-gray-600 dark:text-gray-400">
                    {{ $headerConfig['contact_info']['email_label'] ?? 'Email:' }}
                </span>
                <span class="text-gray-800 dark:text-gray-200">{{ $school->email }}</span>
            </div>
        @endif
    </div>
@endif
{{-- {{dd($headerConfig['report_title'])}} --}}


{{-- Report Title --}}
@if ($headerConfig['show_report_title'] ?? true)
    <h2 style="{{ $reportTitleStyle }} " class="text-gray-900 dark:text-white">
        {{ $termName }} {{ $headerConfig['report_title']['report_title'] ?? 'TERM REPORT CARD' }}
    </h2>
@endif

{{-- Academic Info --}}
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

{{-- resources/views/pdf-report-cards-components/header-content.blade.php --}}
@php
    $headerConfig = $headerConfig ?? [];
    $customStyles = $headerConfig['custom_styles'] ?? [];
    $academicInfo = $headerConfig['academic_info'] ?? [];
    $academicStyles = $academicInfo['styles'] ?? [];
    $rtlConfig = $template->getRtlConfig();

    // Get session and term info with fallbacks
    $sessionName = $data['academic_info']['session']['name'] ?? '';
    $termName = $data['academic_info']['term']['name'] ?? '';

    // Define layoutType with proper fallback
    $layoutType = $headerConfig['logo_position'] ?? 'center';

    // Get print configuration with defaults
    $printConfig = $template->print_config['header'] ?? [];

    // Print-specific dimensions
    $logoHeight = $printConfig['logo_height'] ?? '80';
    $schoolNameFontSize = $printConfig['school_name']['font_size'] ?? '14';
    $addressFontSize = $printConfig['address']['font_size'] ?? '11';
    $contactInfoFontSize = $printConfig['contact_info']['font_size'] ?? '10';
    $reportTitleFontSize = $printConfig['report_title']['font_size'] ?? '12';
    $academicInfoFontSize = $printConfig['academic_info']['font_size'] ?? '10';
    $sectionSpacing = $printConfig['spacing'] ?? '8';

    // Colors from headerConfig
    $textColor = $headerConfig['text_color'] ?? '#111827';
    $subTextColor = $headerConfig['sub_text_color'] ?? '#4B5563';
    $labelColor = $headerConfig['label_color'] ?? '#6B7280';
@endphp
{{-- <div style="{{ $textContainerStyle }}"> --}}

    @if ($rtlConfig['header']['show_arabic_name'] && !empty($rtlConfig['header']['arabic_name']))
        <div class="{{ $rtlConfig['header']['arabic_text_size'] }} font-bold"
            style="font-family: {{ $rtlConfig['arabic_font'] }}; direction: rtl;">
            {{ $rtlConfig['header']['arabic_name'] }}
        </div>
    @endif

    @if ($headerConfig['show_school_name'] ?? true)
        <h1 style="{{ $schoolNameStyle }}; text-transform: {{$textTransform}};">
            {{ $school->name }}
        </h1>
        @if ($headerConfig['show_school_address'] ?? true)
            <p style="{{ $addressStyle }}">{{ $school->address }}</p>
        @endif
    @endif

    @if ($headerConfig['show_contact_information'] ?? true)
        <div style="{{ $contactInfoStyle }} margin-bottom: 0.3rem;">
            @if (($headerConfig['contact_info']['show_phone'] ?? true) && $school->phone)
                <div style="{{ $contactItemStyle }}">
                    <span style="{{ $labelStyle }}">
                        {{ $headerConfig['contact_info']['phone_label'] ?? 'Phone:' }}
                    </span>
                    <span style="">{{ $school->phone }}</span>
                </div>
            @endif

            @if (($headerConfig['contact_info']['show_email'] ?? true) && $school->email)
                <div style="{{ $contactItemStyle }}">
                    <span style="{{ $labelStyle }}">
                        {{ $headerConfig['contact_info']['email_label'] ?? 'Email:' }}
                    </span>
                    <span style="">{{ $school->email }}</span>
                </div>
            @endif
        </div>
    @endif


    @if ($headerConfig['show_report_title'] ?? true)

        <h2 style="{{ $titleInlineStyles }}; text-align:;">
            {{ $termName }} {{ $headerConfig['report_title']['report_title'] ?? 'TERM REPORT CARD' }}
        </h2>
    @endif


    @if (($academicInfo['show_session'] ?? true) || ($academicInfo['show_term'] ?? true))
        @php

            $academicTextStyles = [
                'font-size' => match ($academicStyles['size'] ?? 'text-lg') {
                    'text-sm' => '0.65rem',
                    'text-base' => '0.75rem',
                    'text-lg' => '0.85rem',
                    default => '0.75rem',
                },

                'font-weight' => match ($academicStyles['weight'] ?? 'font-normal') {
                    'font-light' => '300',
                    'font-normal' => '400',
                    'font-medium' => '500',
                    'font-semibold' => '600',
                    'font-bold' => '700',
                    default => '400',
                },

                'color' => match ($academicStyles['color'] ?? 'text-gray-600') {
                    'text-primary-600' => '#2563eb',
                    'text-gray-600' => '#4B5563',
                    'text-gray-700' => '#374151',
                    'text-black' => '#000000',
                    default => '#4B5563',
                },

                'margin-top' => '0.15rem',
                'margin-bottom' => '0rem',
                'line-height' => '1.2',
                'letter-spacing' => '0.01em',
            ];

            $inlineStyles = collect($academicTextStyles)->map(fn($value, $prop) => "{$prop}: {$value};")->implode(' ');
        @endphp

        <p style="{{ $inlineStyles }}">
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
{{-- </div> --}}

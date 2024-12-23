{{-- Include the header section --}}
@php
    $school = Filament\Facades\Filament::getTenant();
    // Get current session and term from data
    $academicInfo = $data['academic_info'] ?? [];
@endphp

@include('report-cards.sections.header', [
    'headerConfig' => $template->getHeaderConfig(),
    'data' => [
        'academic_info' => [
            'session' => $academicInfo['session'] ?? '',
            'term' => $academicInfo['term'] ?? '',
            'session_name' => $academicInfo['session_name'] ?? '',
            'term_name' => $academicInfo['term_name'] ?? '',
        ],
    ],
    'school' => $school,
])

{{-- Student Information Section --}}
@include('report-cards.sections.student-info', [
    'template' => $template,
    'data' => $data,
])

<!-- Grades Table -->
@include('report-cards.sections.grade-table', [
    'template' => $template,
    'data' => [
        'subjects' => $data['subjects'] ?? [],
        'position' => $data['term_summary']['position'] ?? null,
        'total_score' => $data['term_summary']['total_score'] ?? null,
        'average' => $data['term_summary']['average'] ?? null,
        'class_size' => $data['term_summary']['class_size'] ?? null,
    ],
])

{{-- Activities Section --}}
@include('report-cards.sections.activities', [
    'template' => $template,
    'data' => [
        'activities' => $data['activities'] ?? [],
    ],
    'config' => $template->getActivitiesConfig(),
])

{{-- Comments Section --}}
@include('report-cards.sections.comments', [
    'template' => $template,
    'data' => [
        'comments' => $data['comments'] ?? [],
        'activities' => $data['activities'] ?? [],
    ],
    'config' => $template->getCommentsConfig(),
])

{{-- resources/views/pdfs/term-report-card.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Term Report Card</title>
    {{-- <link href="https://cdn.tailwindcss.com" rel="stylesheet"> --}}
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body>
    {{-- {{dd($report)}} --}}
    <div class="report-container">
        <!-- Include your existing sections with the new styling -->
        @include('pdf-report-cards-components.header', [
            'headerConfig' => $report['template']->getHeaderConfig(),
            'template' => $report['template'],
            'data' => [
                'academic_info' => $report['academic_info'],
            ],
            'school' => $school,
        ])


        @include('pdf-report-cards-components.student-info', [
            'template' => $report['template'],
            'data' => [
                'basic_info' => $report['basic_info'],
                'term_summary' => $report['term_summary'],
            ],
        ])

        @include('pdf-report-cards-components.grade-table', [
            'template' => $report['template'],
            'data' => [
                'subjects' => $report['subjects'],
                'term_summary' => $report['term_summary'],
            ],
        ])

        @include('pdf-report-cards-components.activities', [
            'template' => $report['template'],
            'data' => [
                'activities' => $report['activities'] ?? [],
            ],
            'config' => $report['template']->getActivitiesConfig(),
        ])

        @if (!empty($report['comments']))
            @include('pdf-report-cards-components.comments', [
                'template' => $report['template'],
                'data' => [
                    'comments' => $report['comments'],
                ],
                'config' => $report['template']->getCommentsConfig(),
            ])
        @endif


        {{-- @include('report-cards.sections.header', [
            'headerConfig' => $report['template']->getHeaderConfig(),
            'data' => [
                'academic_info' => $report['academic_info'],
            ],
            'school' => $school,
        ]) --}}

        {{-- @include('report-cards.sections.student-info', [
            'template' => $report['template'],
            'data' => [
                'basic_info' => $report['basic_info'],
                'term_summary' => $report['summary'],
            ],
        ]) --}}

        {{-- @include('report-cards.sections.grade-table', [
            'template' => $report['template'],
            'data' => [
                'subjects' => $report['subjects'],
                'summary' => $report['summary'],
            ],
        ]) --}}

        {{-- @include('report-cards.sections.activities', [
            'template' => $report['template'],
            'data' => [
                'activities' => $report['activities'] ?? [],
            ],
            'config' => $report['template']->getActivitiesConfig(),
        ]) --}}

        {{-- @if (!empty($report['comments']))
            @include('report-cards.sections.comments', [
                'template' => $report['template'],
                'data' => [
                    'comments' => $report['comments'],
                ],
                'config' => $report['template']->getCommentsConfig(),
            ])
        @endif --}}
    </div>
</body>

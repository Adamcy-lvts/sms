{{-- resources/views/pdfs/term-report-card.blade.php --}}
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Term Report Card</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body style="font-family: 'Inter', sans-serif; margin: 0; padding: 0; position: relative; min-height: 100vh;">
    <div style="position: relative; width: 100%; min-height: 100vh; z-index: 1;">
        <!-- Watermark -->
        @if($schoolLogo)
            <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 0; opacity: 0.03; pointer-events: none; width: 60%; max-width: 800px;">
                <img src="{{ $schoolLogo }}" alt="" style="width: 100%; height: auto;">
            </div>
        @endif

        <!-- Content wrapper -->
        {{-- <div style="position: relative; z-index: 1;"> --}}
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
        {{-- </div> --}}
    </div>
</body>
</html>

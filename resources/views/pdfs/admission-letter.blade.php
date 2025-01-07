{{-- // resources/views/pdfs/admission-letter.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Naskh+Arabic:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Custom Font Import */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');


        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Print Specific Styles */
        @media print {
            .page-break {
                page-break-after: always;
            }

            body {
                min-height: 100vh;
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                background-color: white;
            }
        }

        /* Custom Typography */
        .letter-spacing-tight {
            letter-spacing: -0.025em;
        }
    </style>
</head>

<body class="bg-white">
    <!-- Main Container -->
    <div class="flex flex-col min-h-screen">
        <!-- Content Wrapper -->
        <div class="flex-1 max-w-4xl mx-auto px-8 py-12 w-full">
            <!-- Header Section -->
            <!-- Header Section with centered watermark and tighter spacing -->
            <header class="mb-8 text-center relative">
                <!-- Large centered watermark -->
                <div class="fixed inset-0 flex items-center justify-center opacity-[0.03] pointer-events-none"
                    style="z-index: -1">
                    <img src="{{ $logoData }}" alt="" class="w-[80%] max-w-3xl">
                </div>

                <!-- Content Container -->
                <!-- Tighter spacing for school info -->
                <div class="relative z-10">
                    <!-- Logo -->
                    @if ($school->logo)
                        <div class="mb-1.5"> <!-- Reduced margin -->
                            <img src="{{ $logoData }}" alt="{{ $school->name }}"
                                class="h-16 w-auto object-contain mx-auto">
                        </div>
                    @endif

                    <!-- School Names with minimal spacing -->
                    <div class="space-y-0"> <!-- Removed space between names -->

                        @if ($school->name_ar)
                            <h2 class="text-base font-semibold text-gray-700 -mt-1"
                                style="font-family: 'Noto Naskh Arabic', serif;">
                                {{ $school->name_ar }}
                            </h2>
                        @endif

                        <h1 class="text-md font-bold tracking-tight text-gray-800 mb-0">
                            {{ strtoupper($school->name) }}
                        </h1>

                        <!-- Contact info closer to names -->
                        <div class="mt-1 space-y-0.5 text-gray-600"> <!-- Minimal top margin -->
                            <p class="text-xs">{{ $school->address }}</p>
                            <div class="flex items-center justify-center gap-2 text-xs">
                                <span>Tel: {{ $school->phone }}</span>
                                <span class="text-gray-400">|</span>
                                <span>{{ $school->email }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Letter Content -->
            <div class="prose prose-sm max-w-none text-gray-800 leading-relaxed">
                {!! $content !!}
            </div>
        </div>

        <!-- Footer -->
        <footer class="w-full border-t border-gray-200 bg-white">
            <div class="max-w-4xl mx-auto px-8 py-6">
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>{{ $school->name }}</span>
                    <span>Page <span class="pageNumber"></span> of <span class="totalPages"></span></span>
                </div>
            </div>
        </footer>
    </div>
</body>

</html>

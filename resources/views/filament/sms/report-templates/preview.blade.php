{{-- resources/views/filament/sms/resources/report-template/preview.blade.php --}}
<div class="bg-white rounded-lg shadow-lg p-6">
    {{-- Header Section --}}
    <div class="text-center mb-6">
        @if($template->header_config['show_logo'])
            <div class="mb-4">
                <img src="{{ auth()->user()->currentTeam->school->logo_url }}" 
                     alt="School Logo" 
                     class="mx-auto"
                     style="height: {{ $template->header_config['logo_height'] }}">
            </div>
        @endif

        @if($template->header_config['show_school_name'])
            <h1 class="text-2xl font-bold">{{ auth()->user()->currentTeam->school->name }}</h1>
        @endif

        @if($template->header_config['show_school_address'])
            <p class="text-gray-600">{{ auth()->user()->currentTeam->school->address }}</p>
        @endif

        <h2 class="text-xl font-semibold mt-4">STUDENT REPORT CARD</h2>
        <p class="text-gray-600">2023/2024 Academic Session - First Term</p>
    </div>

    {{-- Student Information Section --}}
    <div class="grid grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
        @foreach($template->student_info_config['fields'] as $field => $enabled)
            @if($enabled)
                <div class="flex gap-2">
                    <span class="font-semibold">{{ Str::title(str_replace('_', ' ', $field)) }}:</span>
                    <span>{{ $data['student'][$field] }}</span>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Academic Performance Section --}}
    <div class="mb-6">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-2 text-left">Subject</th>
                    @foreach($template->assessment_columns as $column)
                        @if($column['is_visible'])
                            <th class="border p-2 text-center">{{ $column['name'] }} ({{ $column['max_score'] }})</th>
                        @endif
                    @endforeach
                    <th class="border p-2 text-center">Total (100)</th>
                    <th class="border p-2 text-center">Grade</th>
                    <th class="border p-2">Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['scores'] as $score)
                    <tr>
                        <td class="border p-2">{{ $score['subject'] }}</td>
                        @foreach($template->assessment_columns as $column)
                            @if($column['is_visible'])
                                <td class="border p-2 text-center">
                                    {{ $score['assessments'][$column['key']] ?? '-' }}
                                </td>
                            @endif
                        @endforeach
                        @php
                            $total = collect($score['assessments'])->sum();
                            $grade = $template->getGrade($total);
                        @endphp
                        <td class="border p-2 text-center font-semibold">{{ $total }}</td>
                        <td class="border p-2 text-center">
                            <span class="px-2 py-1 rounded" 
                                  style="background-color: {{ $grade['color_code'] }}; color: white;">
                                {{ $grade['grade'] }}
                            </span>
                        </td>
                        <td class="border p-2">{{ $grade['remark'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Comments Section --}}
    <div class="space-y-4">
        @foreach($template->comment_sections as $section)
            <div class="border-t pt-4">
                <h3 class="font-semibold mb-2">{{ $section['name'] }}</h3>
                <div class="p-3 bg-gray-50 rounded">
                    <p class="text-gray-600 italic">Sample comment will appear here...</p>
                </div>
            </div>
        @endforeach
    </div>
</div>
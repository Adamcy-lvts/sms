{{-- <x-filament-panels::page> --}}
<x-guest-layout>

    <div class="prose dark:prose-invert max-w-3xl mx-auto">
        <h1>{{ $document->title }}</h1>
        <div class="text-sm text-gray-500 mb-4">
            Version {{ $document->version }} | Last updated: {{ $document->published_at->format('F j, Y') }}
        </div>

        {{-- {!! $document->content !!} --}}
        {!! tiptap_converter()->asHTML($document->content) !!}
    </div>

    </x-guest-layouts>
    {{-- </x-filament-panels::page> --}}

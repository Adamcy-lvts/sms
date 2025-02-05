{{-- <x-filament-panels::page> --}}
<x-guest-layout>

    {{-- <div class="prose dark:prose-invert max-w-3xl mx-auto">
        <h1>{{ $document->title }}</h1>
        <div class="text-sm text-gray-500 mb-4">
            Version {{ $document->version }} | Last updated: {{ $document->published_at->format('F j, Y') }}
        </div>

        {{-- {!! $document->content !!} --}}
    {{-- {!! tiptap_converter()->asHTML($document->content) !!} --}}
    {{-- </div>  --}}

    <div class="prose prose-sm md:prose-base lg:prose-lg dark:prose-invert max-w-3xl mx-auto">
        <h1 class="text-3xl font-bold mb-6">{{ $document->title }}</h1>
        <div class="text-sm text-gray-500 mb-8">
            Version {{ $document->version }} | Last updated: {{ $document->published_at->format('F j, Y') }}
        </div>

        <!-- Add specific classes for TipTap content -->
        <div
            class="tiptap-content [&>h2]:text-2xl [&>h2]:font-semibold [&>h2]:mt-6 [&>h2]:mb-4
                [&>p]:mb-4 [&>p]:leading-relaxed 
                [&>ul]:list-disc [&>ul]:pl-6 [&>ul]:mb-4">
            {!! tiptap_converter()->asHTML($document->content) !!}
        </div>
    </div>

    </x-guest-layouts>
    {{-- </x-filament-panels::page> --}}

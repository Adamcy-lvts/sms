{{-- // resources/views/filament/sms/components/footer.blade.php --}}
@inject('legalDocs', 'App\Services\LegalDocumentService')
@php
    use Filament\Facades\Filament;
    // Get current year dynamically
    $currentYear = date('Y');

    // Get tenant/school information
    $tenant = Filament::getTenant();

    // Define footer links - can be moved to config file for better management
    $footerLinks = [
        [
            'label' => 'Terms',
            'url' => '#',
        ],
        [
            'label' => 'Privacy Policy',
            'url' => '#',
        ],
        [
            'label' => 'Contact Support',
            'url' => '#',
        ],
    ];
@endphp

{{-- Footer Container with responsive design and dark mode support --}}
{{-- Add a spacer div to create padding for the main content --}}
<div class="h-16 w-full"></div>

{{-- Modified footer with relative positioning instead of fixed --}}
<footer
    class="relative w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600 mt-auto">
    <div class="w-full mx-auto max-w-screen-xl flex flex-col md:flex-row md:items-center md:justify-between">
        {{-- Copyright Section --}}
        <div class="flex items-center mb-4 md:mb-0">
            <span class="text-sm text-gray-500 sm:text-center dark:text-gray-400 flex items-center">
                @if ($tenant && $tenant->logo)
                    <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="h-6 w-auto mr-2">
                @endif
                © {{ $currentYear }}
                <a href="#" class="hover:underline ml-1">{{ $tenant?->name ?? config('app.name') }}</a>
            </span>
        </div>

        {{-- Links Section --}}
        {{-- <div class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 sm:mt-0">
            @foreach ($footerLinks as $link)
                <a href="{{ $link['url'] }}"
                    class="mr-4 hover:underline md:mr-6 transition-colors duration-200 hover:text-primary-500">
                    {{ $link['label'] }}
                </a>
            @endforeach


        </div> --}}

        <div class="flex flex-wrap items-center mt-3 text-sm font-medium text-gray-500 dark:text-gray-400 sm:mt-0">
            <a href="{{ $legalDocs->getTermsUrl() }}"
                class="mr-4 hover:underline md:mr-6 transition-colors duration-200 hover:text-primary-500">
                Terms of Service
            </a>
            <a href="{{ $legalDocs->getPrivacyUrl() }}"
                class="mr-4 hover:underline md:mr-6 transition-colors duration-200 hover:text-primary-500">
                Privacy Policy
            </a>
            <span class="text-xs text-gray-400 dark:text-gray-500">
                v{{ config('app.version', '1.0.0') }}
            </span>
        </div>
    </div>
</footer>

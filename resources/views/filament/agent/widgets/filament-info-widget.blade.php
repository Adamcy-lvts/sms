<x-filament-widgets::widget>
    <x-filament::section>
        @if(auth()->user()->agent->subaccount_code)
            <div>
                <h2 class="text-sm font-semibold">Your Referral Link</h2>
                <p class="text-xs text-gray-400">Share this link to invite others and earn rewards.</p>
            </div>
            <div class="flex items-center">
                <!-- Display the referral link as a clickable anchor element -->
                <a id="referralLink" href="{{ url('/user/register?ref=' . auth()->user()->agent->referral_code) }}"
                    class="text-sm text-blue-400 hover:underline flex-1">
                    {{ url('/user/register?ref=' . auth()->user()->agent->referral_code) }}
                </a>
                <!-- Copy button -->
                <svg onclick="copyToClipboard()" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"
                    class="w-6 h-6 fill-current">
                    <path
                        d="M208 0H332.1c12.7 0 24.9 5.1 33.9 14.1l67.9 67.9c9 9 21.2 14.1 33.9 14.1V336c0 26.5-21.5 48-48 48H208c-26.5 0-48-21.5-48-48V48c0-26.5 21.5-48 48-48zM48 128h80v64H64V448H256V416h64v48c0 26.5-21.5 48-48 48H48c-26.5 0-48-21.5-48-48V176c0-26.5 21.5-48 48-48z" />
                </svg>
            </div>
        @else
            <div>
                <h2 class="inline-flex ring-1 ring-inset rounded-lg px-4 py-2 text-xs font-semibold bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:border-yellow-600 dark:bg-yellow-700 dark:bg-opacity-25 dark:text-yellow-400">Referral Link Pending</h2>
                <p class="text-xs text-gray-400">Your referral link is currently being generated. It will appear here once the process is complete.</p>
            </div>
        @endif
    </x-filament::section>
{{-- mb-2 bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:border-yellow-600 dark:bg-yellow-700 dark:bg-opacity-25 dark:text-yellow-400 --}}
    <script>
        function copyToClipboard() {
            var copyText = document.getElementById("referralLink").href;
            navigator.clipboard.writeText(copyText).then(function() {
                alert("Copied to clipboard!");
            }, function(err) {
                console.error("Could not copy text: ", err);
            });
        }
    </script>
</x-filament-widgets::widget>


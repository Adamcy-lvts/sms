<div>
    {{-- Feedback Modal --}}
    <x-filament::modal id="feedback-modal" wire:model="showModal" :close-by-clicking-away="false" width="md"
        icon="heroicon-o-chat-bubble-left-right" alignment="center" sticky-header sticky-footer>
        <x-slot name="heading">
            @if ($activeFeedback)
                {{ $activeFeedback->title }}
            @else
                @switch($type)
                    @case('bug')
                        Report a Bug
                    @break

                    @case('feature')
                        Request a Feature
                    @break

                    @default
                        Share Your Feedback
                @endswitch
            @endif
        </x-slot>

        <x-slot name="description">
            {{ $activeFeedback?->description ?? 'We value your feedback to improve our service' }}
        </x-slot>

        <div class="space-y-4">
            @unless ($activeFeedback)
                <x-filament::input.wrapper label="Type" required>
                    <x-filament::input.select wire:model="type">
                        <option value="feedback">General Feedback</option>
                        <option value="bug">Report Bug</option>
                        <option value="feature">Feature Request</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            @endunless

            {{-- In your feedback modal view, update the question rendering section --}}

            @if ($activeFeedback && $activeFeedback->questions)
                @foreach ($activeFeedback->questions as $index => $question)
                    <div class="space-y-4 mb-4">
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $question['question'] ?? 'Question ' . ($index + 1) }}
                        </label>

                        @switch($question['type'] ?? 'text')
                            @case('rating')
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-2">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <button type="button"
                                                wire:click="setRating({{ $index }}, {{ $i }})"
                                                class="focus:outline-none">
                                                <svg class="w-8 h-8 {{ isset($responses[$index]['answer']) && $responses[$index]['answer'] >= $i ? 'text-yellow-400' : 'text-gray-300' }}"
                                                    fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                                </svg>
                                            </button>
                                        @endfor
                                    </div>

                                    {{-- Add comment textarea when rating is selected --}}
                                    @if (isset($responses[$index]['answer']))
                                        <x-filament::input.wrapper label="Additional Comments (Optional)">
                                            <textarea wire:model="responses.{{ $index }}.comment" rows="3"
                                                class="block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition duration-75"
                                                placeholder="Please share more about your rating..."></textarea>
                                        </x-filament::input.wrapper>
                                    @endif
                                </div>
                            @break

                            @case('select')
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="responses.{{ $index }}.answer">
                                        <option value="">Select an option</option>
                                        @foreach ($question['options'] ?? [] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            @break

                            @default
                                <x-filament::input.wrapper>
                                    <x-filament::input type="text" wire:model="responses.{{ $index }}.answer"
                                        placeholder="Your answer" />
                                </x-filament::input.wrapper>
                        @endswitch
                    </div>
                @endforeach
            @else
                <x-filament::input.wrapper label="Title" required>
                    <x-filament::input wire:model="title" type="text" placeholder="Brief summary of your feedback" />
                </x-filament::input.wrapper>

                {{-- Custom Textarea with Filament styling --}}
                <x-filament::input.wrapper label="Description" required>
                    <textarea wire:model="description" rows="4"
                        class="block w-full rounded-lg shadow-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:text-sm transition duration-75"
                        placeholder="Please provide detailed information..."></textarea>
                </x-filament::input.wrapper>
            @endif
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="resetForm" color="gray" size="sm">
                Cancel
            </x-filament::button>

            <x-filament::button wire:click="submit" type="submit" size="sm">
                Submit Feedback
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    {{-- Add a spacer div to create padding for the main content --}}
    <div class="h-16 w-full"></div>

    <footer
        class="relative w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-between md:p-6 dark:bg-gray-800 dark:border-gray-600 mt-auto">
        <div class="w-full mx-auto max-w-screen-xl flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Left Section: Copyright -->
            <div class="flex items-center">
                <span class="text-sm text-gray-500 dark:text-gray-400 flex items-center">
                    @if ($tenant && $tenant->logo)
                        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}"
                            class="h-6 w-auto mr-2">
                    @endif
                    © {{ $currentYear }}
                    <a href="#" class="hover:underline ml-1">{{ $tenant?->name ?? config('app.name') }}</a>
                </span>
            </div>

            <!-- Center Section: Links -->
            <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                <a href="{{ $legalDocs->getTermsUrl() }}" target="_blank"
                    class="hover:underline transition-colors duration-200 hover:text-primary-500">Terms of Service</a>
                <a href="{{ $legalDocs->getPrivacyUrl() }}"
                    class="hover:underline transition-colors duration-200 hover:text-primary-500">Privacy Policy</a>
                <button wire:click="openFeedbackModal" target="_blank"
                    class="hover:underline transition-colors duration-200 hover:text-primary-500">Give Us Your Feedback</button>
                <span class="text-xs">v{{ config('app.version', '1.0.0') }}</span>
            </div>

            <!-- Right Section: Powered by Devcentric -->
            <div class="flex items-center text-sm text-gray-500 dark:text-gray-400">
        
                <a href="https://devcentricstudio.com" target="_blank" rel="noopener noreferrer"
                    class="flex items-center hover:opacity-80 transition-opacity duration-200" ><span class="mr-2">Powered by Devcentric Studio</span>
                   <br> <img src="{{ asset('img/devcentric_logo_1.png') }}" alt="Devcentric Studio" class="h-4 w-auto">
                </a>
            </div>
        </div>
    </footer>


</div>

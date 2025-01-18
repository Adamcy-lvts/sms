<div>
    <!-- Header Section with improved spacing and typography -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 ">
        <div class="text-center max-w-3xl mx-auto mb-16">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                Comprehensive School Management Plans
            </h2>
            <p class="text-lg text-gray-600">
                Choose the perfect plan tailored to your school's needs. From basic administrative functions to advanced
                analytics and integrations, we have everything covered.
            </p>
        </div>

        <!-- Billing Toggle with fixed widths -->
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center justify-center gap-3 relative min-w-[300px]">
                <span class="text-sm font-medium w-28 text-right {{ !$isAnnual ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                    Monthly 
                </span>
                <button type="button" wire:click="toggleBilling"
                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-2 {{ $isAnnual ? 'bg-green-600' : 'bg-gray-200' }}">
                    <span class="sr-only">Toggle billing period</span>
                    <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isAnnual ? 'translate-x-5' : 'translate-x-0' }}">
                    </span>
                </button>
                <div class="w-28 flex items-center">
                    <span class="text-sm font-medium {{ $isAnnual ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                        Yearly 
                    </span>
                    
                </div>
            </div>
        </div>

        <!-- Plans Grid with improved spacing and card styling -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8">
            @forelse($plans as $plan)
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300 relative overflow-hidden"
                    wire:key="plan-{{ $plan->id }}">
                    <div class="p-8">
                        <h2 class="text-xl font-semibold text-gray-900 mb-2">{{ $plan->name }}</h2>
                        <p class="text-gray-600 text-sm mb-6">
                            {{ Str::limit($plan->description, 100) }}
                        </p>

                        <!-- Pricing section -->
                        <div class="mb-8">
                            <span class="text-4xl font-bold text-gray-900">
                                {{ $this->formatPrice($plan) }}
                            </span>
                            <span class="text-gray-500">/{{ $isAnnual ? 'year' : 'month' }}</span>

                            @if ($isAnnual && $plan->yearly_discount > 0)
                                <div class="mt-2 text-sm text-green-600">
                                    Save {{ $plan->yearly_discount }}% with annual billing
                                </div>
                                <div class="mt-1 text-sm text-gray-400 line-through">
                                    {{ formatNaira($plan->price) }}/year
                                </div>
                            @endif
                        </div>

                        <!-- Replace button with link using query parameters -->
                        <a href="/sms/register?plan={{ $plan->id }}&billing={{ $isAnnual ? 'annual' : 'monthly' }}"
                            class="inline-block w-full px-4 py-3 text-sm font-medium text-center text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                            {{ $plan->cto }}
                        </a>

                        @if ($plan->has_trial)
                            <p class="mt-3 text-sm text-center text-gray-500">
                                {{ $plan->trial_period }} days free trial
                            </p>
                        @endif
                    </div>

                    <!-- Features List with improved styling -->
                    <div class="border-t border-gray-100 p-8 bg-gray-50">
                        <h3 class="font-semibold text-gray-900 mb-4">Features:</h3>
                        <ul class="space-y-3">
                            @forelse($plan->features as $feature)
                                <li class="flex items-start">
                                    <svg class="h-5 w-5 text-green-500 mt-0.5 mr-2" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span class="text-gray-600">{{ $feature }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500">No features listed for this plan.</li>
                            @endforelse
                        </ul>

                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500">
                    <p>No pricing plans are currently available. Please check back later.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>

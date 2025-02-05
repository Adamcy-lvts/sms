<x-filament-panels::page>

    <div>
        <!-- Header Section -->
        <div class="text-center mb-8 px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold mb-4 dark:text-white">
                Comprehensive School Management Plans
            </h2>
            <p class="text-sm sm:text-md text-gray-600 dark:text-gray-300 mb-6">
                Choose the perfect plan tailored to your school's needs. From basic administrative functions to advanced
                analytics and integrations, we have everything covered.
            </p>

            <!-- Billing Toggle -->
            <div class="flex items-center justify-center mb-8">
                <div class="flex items-center justify-center gap-3 relative min-w-[300px]">
                    <span class="text-sm font-medium w-28 text-right {{ !$isAnnual ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                        Monthly 
                    </span>
                    <button type="button" wire:click="toggleBilling"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 {{ $isAnnual ? 'bg-emerald-600' : 'bg-gray-200' }}">
                        <span class="sr-only">Toggle billing period</span>
                        <span class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $isAnnual ? 'translate-x-5' : 'translate-x-0' }}">
                        </span>
                    </button>
                    <div class="w-28 flex items-center">
                        <span class="text-sm font-medium {{ $isAnnual ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-500 dark:text-gray-400' }}">
                            Yearly 
                           
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plans Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 justify-items-center">
            @forelse ($pricingPlans as $plan)
                <div
                    class="w-full max-w-sm bg-white rounded-lg shadow-lg dark:bg-gray-800 overflow-hidden 
                        {{ $school->hasActiveSubscription($plan->id) ? 'ring-2 ring-emerald-500' : '' }}">
                    <div class="p-6">
                        <!-- Plan Header -->
                        <h2 class="text-xl font-semibold dark:text-white mb-2">{{ $plan->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                            {{ Str::limit($plan->description, 100) }}
                        </p>

                        <!-- Pricing -->
                        <!-- Pricing -->
                        <div class="mb-6">
                            <span class="text-4xl font-bold dark:text-white">
                                @if ($isAnnual && $plan->discounted_price !== null)
                                    {{ formatNaira($plan->discounted_price) }}
                                @else
                                    {{ formatNaira($plan->price) }}
                                @endif
                            </span>
                            <span class="text-gray-600 dark:text-gray-300 text-base">
                                /{{ $isAnnual ? 'year' : 'month' }}
                            </span>

                            @if ($isAnnual && $plan->yearly_discount > 0)
                                <div class="mt-2 text-sm text-emerald-600 dark:text-emerald-400">
                                    Save {{ $plan->yearly_discount }}% with annual billing
                                </div>
                                <div class="mt-1 text-sm text-gray-500 dark:text-gray-400 line-through">
                                    {{ formatNaira($plan->price) }}/year
                                </div>
                            @endif
                        </div>

                        <!-- Action Button -->
                        @if ($school->hasActiveSubscription($plan->id))
                            <div
                                class="text-center p-2 rounded bg-emerald-100 text-emerald-700 dark:bg-emerald-700 dark:bg-opacity-25 dark:text-emerald-200">
                                Active Plan
                            </div>
                        @elseif ($plan->name === 'Explorer Access Plan')
                            <div class="text-center p-2 rounded border font-bold text-xl dark:text-white">
                                {{ $plan->cto }}
                            </div>
                        @else
                            @php
                                $paymentForm = App\Filament\Sms\Pages\PaymentForm::getUrl([
                                    'id' => $plan->id,
                                    'billing' => $isAnnual ? 'annual' : 'monthly',
                                ]);
                            @endphp
                            <x-filament::button color="primary" tag="a" href="{{ $paymentForm }}"
                                class="w-full">
                                {{ $plan->cto }}
                            </x-filament::button>

                            @if ($plan->has_trial)
                                <p class="mt-2 text-sm text-center text-gray-500 dark:text-gray-400">
                                    {{ $plan->trial_period }} days free trial
                                </p>
                            @endif
                        @endif
                    </div>

                    <!-- Features List -->
                    <div class="bg-gray-50 dark:bg-gray-700 p-6">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white">Features:</h3>

                        @php
                            $planTierLevel = match($plan->name) {
                                'Basic' => 1,
                                'Standard' => 2,
                                'Premium' => 3,
                                default => 0
                            };
                            
                            $tierFeatures = $plan->features->filter(function($feature) use ($planTierLevel) {
                                return \App\Models\Feature::getTierLevel($feature->slug) === $planTierLevel;
                            });
                            
                            $inheritanceLabel = match($planTierLevel) {
                                2 => 'All Basic features +',
                                3 => 'All Standard features +',
                                default => ''
                            };
                        @endphp

                        @if ($inheritanceLabel)
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">{{ $inheritanceLabel }}</p>
                        @endif

                        <ul class="space-y-2">
                            @forelse ($tierFeatures as $feature)
                                <li class="flex items-center text-sm dark:text-gray-300">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ $feature->name }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">No features listed for this plan.</li>
                            @endforelse
                        </ul>

                        @if ($plan->hasLimits())
                            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                <h4 class="text-sm font-medium mb-2 dark:text-white">Usage Limits:</h4>
                                <ul class="space-y-1">
                                    @if ($plan->max_students)
                                        <li class="text-sm text-gray-600 dark:text-gray-300">Up to
                                            {{ number_format($plan->max_students) }} students</li>
                                    @endif
                                    @if ($plan->max_staff)
                                        <li class="text-sm text-gray-600 dark:text-gray-300">Up to
                                            {{ number_format($plan->max_staff) }} staff Login Accounts</li>
                                    @endif
                                    @if ($plan->max_classes)
                                        <li class="text-sm text-gray-600 dark:text-gray-300">Up to
                                            {{ number_format($plan->max_classes) }} classes</li>
                                    @endif
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-gray-500 dark:text-gray-400">
                    <p>No pricing plans are currently available. Please check back later.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>

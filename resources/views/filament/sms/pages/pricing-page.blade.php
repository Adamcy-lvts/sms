<x-filament-panels::page>
    <div>
        <div class="text-center mb-8 px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold mb-4 dark:text-white">
                Comprehensive School Management Plans
            </h2>
            <p class="text-sm sm:text-md text-gray-600 dark:text-gray-300">
                Choose the perfect plan tailored to your school's needs. From basic administrative functions to advanced
                analytics and integrations, we have everything covered.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 justify-items-center">
            @forelse ($pricingPlans as $plan)
                <div
                    class="w-full max-w-sm bg-white rounded-lg shadow-lg dark:bg-gray-800 overflow-hidden 
                            {{ $school->hasActiveSubscription($plan->id) ? 'ring-2 ring-emerald-500' : '' }}">
                    <div class="p-6">
                        <h2 class="text-xl font-semibold dark:text-white mb-2">{{ $plan->name }}</h2>
                        <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">
                            {{ Str::limit($plan->description, 100) }}
                        </p>
                        <div class="mb-6">
                            <span class="text-4xl font-bold dark:text-white">{{ formatNaira($plan->price) }}</span>
                            <span class="text-gray-600 dark:text-gray-300 text-base">/month</span>
                        </div>

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
                            <form method="POST" action="{{ route('pay') }}" class="mb-4">
                                @csrf
                                <input type="hidden" name="email" value="{{ $school->email }}">
                                <input type="hidden" name="amount" value="{{ $plan->price * 100 }}">
                                <input type="hidden" name="currency" value="NGN">
                                <input type="hidden" name="planId" value="{{ $plan->id }}">
                                <button type="submit"
                                    class="w-full bg-emerald-500 text-white p-2 rounded hover:bg-emerald-600 transition duration-300 ease-in-out dark:hover:bg-emerald-700">
                                    {{ $plan->cto }}
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 p-6">
                        <h3 class="text-lg font-semibold mb-3 dark:text-white">Features:</h3>
                        <ul class="space-y-2">
                            @forelse ($plan->features as $feature)
                                <li class="flex items-center text-sm dark:text-gray-300">
                                    <svg class="h-5 w-5 text-emerald-500 mr-2" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span>{{ $feature }}</span>
                                </li>
                            @empty
                                <li class="text-gray-500 dark:text-gray-400">No features listed for this plan.</li>
                            @endforelse
                        </ul>
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

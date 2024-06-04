<x-filament-panels::page>
    <div class="text-center mb-8 px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold mb-4 dark:text-white">
            Comprehensive School Management Plans
        </h2>
        <p class="text-sm sm:text-md text-gray-600 dark:text-gray-300">
            Choose the perfect plan tailored to your school's needs. From basic administrative functions to advanced
            analytics and integrations, we have everything covered.
        </p>
    </div>


    <div class="flex flex-col md:flex-row md:space-x-4 justify-center">
        @foreach ($pricingPlans as $plan)
            <div
                class="max-w-sm mx-auto my-4 p-6 bg-white rounded-lg shadow-lg dark:bg-gray-800 {{ $school->hasActiveSubscription($plan->id) ? 'border-2 border-emerald-500' : '' }}">
                <h2 class="text-lg font-semibold dark:text-white">{{ $plan->name }}</h2>
                <p class="text-gray-600 dark:text-gray-300 text-sm my-2">
                    {{ $plan->description }}
                </p>
                <div class="my-4">
                    <span class="text-4xl font-bold dark:text-white">{{ formatNaira($plan->price) }} <span
                            class="text-sm">/month</span> </span>
                    <span class="text-gray-600 dark:text-gray-300 text-base">{{ $plan->currency }}</span>
                </div>

                @if ($school->hasActiveSubscription($plan->id))
                    <div
                        class="text-center p-2 rounded border border-emerald-600 bg-emerald-100 text-emerald-700 dark:border-emerald-400 dark:bg-emerald-700 dark:bg-opacity-25 dark:text-emerald-200">
                        Active Plan
                    </div>
                @elseif ($plan->title === 'Explorer Access Plan')
                    <div class="text-center p-2 rounded border font-bold text-xl dark:text-white">
                        {{ $plan->cto }}
                    </div>
                @else
                    <div class="flex flex-col">
                        <form method="POST" action="{{ route('pay') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $school->email }}">
                            {{-- required --}}
                            <input type="hidden" name="amount" value="{{ $plan->price }}"> {{-- required in kobo --}}
                            <input type="hidden" name="currency" value="NGN">
                            {{-- For other necessary things you want to add to your payload. it is optional though --}}
                            <input type="hidden" name="metadata"
                                value="{{ json_encode($array = ['planId' => $plan->id]) }}">
                            <input type="hidden" name="reference" value="{{ Paystack::genTranxRef() }}">
                            <button
                                class="flex w-full items-center justify-center bg-emerald-500 text-white p-2 rounded my-8 hover:bg-emerald-600 transition duration-300 ease-in-out dark:hover:bg-emerald-800 dark:hover:bg-opacity-25">
                                {{ $plan->cto }}
                            </button>
                        </form>

                    </div>
                @endif

                <ul>
                    @foreach (json_decode($plan->features, true) as $feature)
                        <li class="flex items-center my-2 text-sm dark:text-gray-300">
                            <svg class="h-5 w-5 text-emerald-500 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>

            </div>
        @endforeach
    </div>
</x-filament-panels::page>

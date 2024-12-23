<x-filament-panels::page>
    {{-- Report Controls --}}
    <div class="mb-6">
        {{ $this->form }}
    </div>

    {{-- Loading State --}}
    @if ($this->isLoading)
        <div class="flex justify-center items-center p-4">
            <x-filament::loading-indicator class="h-8 w-8" />
            <span class="ml-2 text-gray-600">Generating Report...</span>
        </div>
    @endif

    <x-filament::button wire:click="generateReport" type="button" size="lg" color="primary">
        <span wire:loading.remove wire:target="generateReport">
            Generate Report
        </span>
        <span wire:loading wire:target="generateReport">
            Generating...
        </span>
    </x-filament::button>
    {{-- {{ dd($this->report) }} --}}
    @if ($this->report)
        {{-- Report Header --}}
        <div class="mb-8">
            <div class="text-center">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Financial Report
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    @if ($this->report['period']['type'] === 'term')
                        Term: {{ $this->report['period']['term_name'] }}
                    @elseif($this->report['period']['type'] === 'session')
                        Session: {{ $this->report['period']['session_name'] }}
                    @else
                        {{ $this->report['period']['start_date'] }}
                    @endif
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ Carbon\Carbon::parse($this->report['period']['start_date'])->format('M d, Y') }} -
                    {{ Carbon\Carbon::parse($this->report['period']['end_date'])->format('M d, Y') }}
                </p>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 gap-6 mb-8 md:grid-cols-3">
            {{-- Income Card --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Total Income
                        </h3>
                        <p class="mt-1 text-3xl font-semibold text-primary-600 dark:text-primary-400">
                            {{ formatNaira($this->report['summary']['total_income']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-primary-100 rounded-full dark:bg-primary-800">
                        <x-heroicon-o-banknotes class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->report['income']['transactions'] }} transactions
                    </p>
                </div>
            </x-filament::section>

            {{-- Expenses Card --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Total Expenses
                        </h3>
                        <p class="mt-1 text-3xl font-semibold text-danger-600 dark:text-danger-400">
                            {{ formatNaira($this->report['summary']['total_expenses']) }}
                        </p>
                    </div>
                    <div class="p-3 bg-danger-100 rounded-full dark:bg-danger-800">
                        <x-heroicon-o-credit-card class="w-8 h-8 text-danger-600 dark:text-danger-400" />
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $this->report['expenses']['transactions'] }} transactions
                    </p>
                </div>
            </x-filament::section>

            {{-- Net Profit Card --}}
            <x-filament::section>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Net Profit
                        </h3>
                        <p
                            class="mt-1 text-3xl font-semibold {{ $this->report['summary']['net_profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            {{ formatNaira($this->report['summary']['net_profit']) }}
                        </p>
                    </div>
                    <div
                        class="p-3 {{ $this->report['summary']['net_profit'] >= 0 ? 'bg-success-100 dark:bg-success-800' : 'bg-danger-100 dark:bg-danger-800' }} rounded-full">
                        <x-heroicon-o-chart-bar
                            class="w-8 h-8 {{ $this->report['summary']['net_profit'] >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}" />
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Profit Margin: {{ number_format($this->report['summary']['profit_margin'], 1) }}%
                    </p>
                </div>
            </x-filament::section>
        </div>

        {{-- Detailed Breakdown --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- {{ dd($this->report) }} --}}
            {{-- Income Breakdown --}}
            <x-filament::section>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                    Income Breakdown
                </h3>

                <div class="space-y-4">
                    @foreach ($this->report['income']['by_type'] as $type)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="space-y-1">
                                        <h4 class="font-medium text-gray-900 dark:text-white">{{ $type['name'] }}</h4>
                                        <div
                                            class="flex items-center space-x-3 text-sm text-gray-500 dark:text-gray-400">
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $type['count'] }} transactions • {{ $type['student_count'] }}
                                                students
                                            </div>
                                            {{-- {{dd($this->report)}} --}}
                                            <div class="flex justify-between items-center">
                                                <!-- Payment Status -->


                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                                            {{ formatNaira($type['amount']) }}
                                        </span>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatNaira($type['amount'] / $type['count']) }} avg/transaction
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

            </x-filament::section>

            {{-- Expense Breakdown --}}
            <x-filament::section>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-6">
                    Expense Breakdown
                </h3>

                <div class="space-y-6">
                    @foreach ($this->report['expenses']['by_category'] as $categoryName => $categoryData)
                        <div
                            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <!-- Category Header -->
                            <div
                                class="flex justify-between items-center p-4 border-b border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
                                <div class="flex items-center space-x-3">
                                    <span class="h-2 w-2 rounded-full bg-primary-500"></span>
                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $categoryName }}</h4>
                                </div>
                                <span class="text-lg font-semibold text-gray-900 dark:text-white">
                                    {{ formatNaira($categoryData['total']) }}
                                </span>
                            </div>

                            @if (!empty($categoryData['items']))
                                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                    @foreach ($categoryData['items'] as $item)
                                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                            <div class="flex justify-between items-center">
                                                <div class="flex-1">
                                                    <h5 class="font-medium text-gray-900 dark:text-white">
                                                        {{ $item['name'] }}</h5>
                                                    <div
                                                        class="mt-1 flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4">
                                                        <span>{{ $item['quantity'] }} {{ $item['unit'] }}</span>
                                                        <span>{{ formatNaira($item['unit_price']) }} each</span>
                                                    </div>
                                                </div>
                                                <span class="text-right font-medium text-gray-900 dark:text-white">
                                                    {{ formatNaira($item['amount']) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
        {{-- Add after the main income/expense breakdown --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <!-- Collection Performance -->
            <x-filament::section>
                <h3 class="font-medium text-lg mb-4">Collection Performance</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">
                            {{ $this->report['income']['collection_metrics']['on_time_payments'] }}</div>
                        <div class="text-sm text-gray-600">On-time Payments</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">
                            {{ $this->report['income']['collection_metrics']['late_payments'] }}</div>
                        <div class="text-sm text-gray-600">Late Payments</div>
                        <div class="text-xs text-gray-500">Avg Delay:
                            {{ number_format($this->report['income']['collection_metrics']['average_delay']) }} days</div>
                    </div>
                </div>
            </x-filament::section>

            <!-- Payment Method Stats -->
            <x-filament::section>
                <h3 class="font-medium text-lg mb-4">Payment Methods</h3>
                <div class="space-y-4">
                    @foreach ($this->report['income']['payment_methods'] as $method => $stats)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <div>
                                <div class="font-medium">{{ $method }}</div>
                                <div class="text-sm text-gray-500">{{ $stats['count'] }} transactions</div>
                            </div>
                            <div class="text-right">
                                <div class="font-bold">{{ formatNaira($stats['amount']) }}</div>
                                <div class="text-sm text-gray-500">Avg: {{ formatNaira($stats['average']) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>

        <!-- Time Analysis -->
        <div class="mt-6">
            <x-filament::section>
                <h3 class="font-medium text-lg mb-4">Time-Based Analysis</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <h4 class="font-medium mb-2">Peak Collection Hours</h4>
                        <div class="space-y-2">
                            @foreach ($this->report['income']['peak_collection_hours'] as $hour => $count)
                                <div class="flex justify-between text-sm">
                                    <span>{{ Carbon\Carbon::createFromFormat('H', $hour)->format('h:i A') }}</span>
                                    <span class="font-medium">{{ $count }} payments</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">Weekday Distribution</h4>
                        <div class="space-y-2">
                            @foreach ($this->report['income']['weekday_analysis'] as $day => $amount)
                                <div class="flex justify-between text-sm">
                                    <span>{{ $day }}</span>
                                    <span class="font-medium">{{ formatNaira($amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">Monthly Overview</h4>
                        <div class="space-y-2">
                            @foreach ($this->report['income']['monthly_trends'] as $month => $data)
                                <div class="flex justify-between text-sm">
                                    <span>{{ $month }}</span>
                                    <div class="text-right">
                                        <div>{{ formatNaira($data['income']) }}</div>
                                        <div class="text-xs text-gray-500">{{ $data['student_count'] }} students</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
        {{-- School Days Information --}}
        @if (isset($this->report['income']['period']['school_days']))
            <x-filament::section class="mt-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                    Period Information
                </h3>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total School Days</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ $this->report['income']['period']['school_days'] }} days
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Daily Income Average</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ formatNaira($this->report['income']['daily_average']) }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Daily Expense Average</p>
                        <p class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ formatNaira($this->report['expenses']['daily_average']) }}
                        </p>
                    </div>
                </div>
            </x-filament::section>
        @endif

        {{-- Terms Breakdown (for session reports) --}}
        @if ($this->periodType === 'session' && isset($this->report['period']['terms']))
            <x-filament::section class="mt-6">
                <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-white">
                    Term-wise Breakdown
                </h3>
                <div class="space-y-4">
                    @foreach ($this->report['period']['terms'] as $term)
                        <div class="p-4 bg-gray-50 rounded-lg dark:bg-gray-800">
                            <h4 class="font-medium text-gray-900 dark:text-white">
                                {{ $term['name'] }}
                            </h4>
                            <div class="grid grid-cols-2 gap-4 mt-2 sm:grid-cols-4">
                                <div>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">School Days</p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ $term['school_days'] }}
                                    </p>
                                </div>
                                {{-- Add more term-specific metrics here --}}
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    @else
        <x-filament::section>
            <div class="text-center">
                <p class="text-gray-600 dark:text-gray-400">
                    Select a period and click 'Generate Report' to view the financial report.
                </p>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>

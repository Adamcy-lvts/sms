<x-filament-panels::page>
    <div class="max-w-4xl mx-auto print:max-w-none">
        <!-- Receipt Card -->
        <div class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-800">
            <!-- Header -->
            <div class="p-4 sm:p-6 md:p-8">
                <div class="flex flex-col sm:flex-row justify-between gap-4 sm:gap-6">
                    <!-- Left Side - School Info -->
                    <div class="flex flex-col sm:flex-row items-center sm:items-start sm:space-x-4 w-full sm:w-auto">
                        <!-- School Logo/Initial -->
                        <div
                            class="flex-shrink-0 bg-gray-100 dark:bg-gray-800 rounded-lg h-16 sm:h-12 w-16 sm:w-12 flex items-center justify-center mb-3 sm:mb-0">
                            @if ($this->record->school->logo)
                                <img src="{{ Storage::url($this->record->school->logo) }}" alt="School Logo"
                                    class="h-12 w-12 sm:h-10 sm:w-10 object-contain" />
                            @else
                                <span class="text-2xl sm:text-xl font-bold text-gray-400 dark:text-gray-600">
                                    {{ substr($this->record->school->name, 0, 2) }}
                                </span>
                            @endif
                        </div>

                        <div class="min-w-0 text-center sm:text-left">
                            <div class="flex flex-col sm:flex-row sm:items-baseline gap-1 sm:gap-4">
                                <h1 class="text-lg sm:text-2xl font-bold text-gray-900 dark:text-white">
                                    {{ $this->record->school->name }}
                                </h1>
                                <h2 class="text-sm sm:text-lg font-semibold text-blue-600 dark:text-blue-400">PAYMENT
                                    RECEIPT</h2>
                            </div>
                            <div class="mt-1 sm:mt-2 space-y-0.5 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                <p class="line-clamp-1">{{ $this->record->school->address }}</p>
                                <p>Phone: {{ $this->record->school->phone }}</p>
                                <p>Email: {{ $this->record->school->email }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - QR and Receipt Info -->
                    <div class="flex sm:flex-col justify-between sm:text-right">
                        <!-- QR Code (Hidden on mobile) -->
                        <div
                            class="hidden sm:flex sm:mb-4 w-24 h-24 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg items-center justify-center self-end ml-auto">
                            <span class="text-xs text-gray-400 dark:text-gray-500">QR Code</span>
                        </div>

                        <!-- Receipt Info -->
                        <div class="w-full sm:w-auto space-y-1 text-xs sm:text-sm text-center sm:text-right">
                            <p class="text-gray-600 dark:text-gray-400">
                                Reference: <span
                                    class="text-gray-900 dark:text-gray-300">{{ $this->record->reference }}</span>
                            </p>
                            <p class="text-gray-600 dark:text-gray-400">
                                Date: <span class="text-gray-900 dark:text-gray-300">
                                    {{ Carbon\Carbon::parse($this->record->paid_at)->format('F j, Y') }}
                                </span>
                            </p>
                            <p
                                class="text-gray-600 dark:text-gray-400 flex justify-center sm:justify-end items-center gap-1">
                                Payment Status:
                                <span @class([
                                    'px-1.5 sm:px-2 py-0.5 rounded-full text-xs inline-block',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' =>
                                        $this->record->status?->name === 'Paid',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300' =>
                                        $this->record->status?->name === 'Partial',
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300' =>
                                        $this->record->status?->name === 'Pending',
                                ])>{{ $this->record->status?->name }}</span>
                            </p>

                        </div>
                    </div>
                </div>
            </div>

            <!-- For Balance Payment: Add this after the Payment Status -->
            @if ($this->record->is_balance_payment)
                <div class="mt-2 px-4 sm:px-6 md:px-8">
                    <div
                        class="py-3 px-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center justify-between">
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                Original Payment Reference:
                            </div>
                            <div class="text-xs sm:text-sm font-medium text-gray-900 dark:text-gray-300">
                                {{ $this->record->originalPayment->reference }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-2">
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                Original Payment Date:
                            </div>
                            <div class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                                {{ Carbon\Carbon::parse($this->record->originalPayment->paid_at)->format('M j, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif
            <!-- Student Info -->
            <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-6 bg-gray-50/50 dark:bg-gray-800/50">
                <div class="grid grid-cols-2 gap-x-2 sm:gap-x-8 gap-y-2 sm:gap-y-3 text-xs sm:text-sm">
                    <!-- Left Column -->
                    <div class="space-y-2 sm:space-y-3">
                        <p class="text-gray-600 dark:text-gray-400 truncate">
                            Student: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ $this->record->student->full_name }}</span>
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 truncate">
                            Class: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ $this->record->classRoom?->name }}</span>
                        </p>
                        <p class="text-gray-600 dark:text-gray-400">
                            Due Date: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ Carbon\Carbon::parse($this->record->due_date)->format('M j, Y') }}</span>
                        </p>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-2 sm:space-y-3">
                        <p class="text-gray-600 dark:text-gray-400 truncate">
                            Session: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ $this->record->academicSession?->name }}</span>
                        </p>
                        <p class="text-gray-600 dark:text-gray-400 truncate">
                            Term: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ $this->record->term?->name }}</span>
                        </p>
                        <p class="text-gray-600 dark:text-gray-400">
                            Paid Date: <span
                                class="text-gray-900 dark:text-gray-300 font-medium">{{ Carbon\Carbon::parse($this->record->paid_at)->format('M j, Y') }}</span>
                        </p>
                    </div>
                </div>
            </div>


            <!-- Payment Details -->
            <!-- Updated Payment Details Section -->
            <div class="p-4 sm:p-6 md:p-8">
                <!-- Modified Headers -->
                <div class="grid grid-cols-12 gap-2 sm:gap-4 pb-3 border-b border-gray-200 dark:border-gray-700">
                    <div class="col-span-3 text-left text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">
                        Payment Type
                    </div>
                    <div class="col-span-2 text-center text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">
                        Method
                    </div>
                    <div class="col-span-2 text-right text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">
                        Amount
                    </div>
                    <div class="col-span-2 text-right text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">
                        Paid
                    </div>
                    <div class="col-span-3 text-right text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">
                        Balance
                    </div>
                </div>

                <!-- Updated Payment Rows -->
                @foreach ($this->record->paymentItems as $item)
                    <div class="grid grid-cols-12 gap-2 sm:gap-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <div class="col-span-3 text-left text-xs sm:text-sm text-gray-900 dark:text-gray-300">
                            {{ $item->paymentType?->name }}
                        </div>
                        <div class="col-span-2 text-center text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                            {{ $this->record->paymentMethod?->name }}
                        </div>
                        <div
                            class="col-span-2 text-right text-xs sm:text-sm text-gray-900 dark:text-gray-300 font-medium">
                            {{ formatNaira($item->amount) }}
                        </div>
                        <div
                            class="col-span-2 text-right text-xs sm:text-sm text-green-600 dark:text-green-400 font-medium">
                            {{ formatNaira($item->deposit) }}
                        </div>
                        <div class="col-span-3 text-right text-xs sm:text-sm">
                            <span @class([
                                'font-medium',
                                'text-gray-900 dark:text-gray-300' => $item->balance == 0,
                                'text-red-600 dark:text-red-400' => $item->balance > 0,
                            ])>
                                {{ formatNaira($item->balance) }}
                            </span>
                        </div>
                    </div>
                @endforeach

                <!-- Updated Summary Section -->
                <div class="mt-4 sm:mt-6 space-y-2 sm:space-y-3 max-w-[12rem] sm:max-w-sm ml-auto">
                    <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Amount</span>
                            <span class="text-xs sm:text-sm text-gray-900 dark:text-gray-300">
                                {{ formatNaira($this->record->amount) }}
                            </span>
                        </div>

                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs sm:text-sm text-gray-600 dark:text-gray-400">Total Paid</span>
                            <span class="text-xs sm:text-sm font-bold text-green-600 dark:text-green-400">
                                {{ formatNaira($this->record->deposit) }}
                            </span>
                        </div>

                        <div
                            class="flex justify-between items-center mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                            <span class="text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-400">Total
                                Balance</span>
                            <span @class([
                                'text-xs sm:text-sm font-bold',
                                'text-gray-900 dark:text-gray-300' => $this->record->balance == 0,
                                'text-red-600 dark:text-red-400' => $this->record->balance > 0,
                            ])>{{ formatNaira($this->record->balance) }}</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Payer Info -->
            <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-6 bg-gray-50/50 dark:bg-gray-800/50">
                <h3 class="font-medium text-xs sm:text-sm text-gray-900 dark:text-gray-300 mb-2 sm:mb-3">Payer
                    Information:</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-4 text-xs sm:text-sm">
                    <p class="text-gray-600 dark:text-gray-400">
                        Name: <span class="text-gray-900 dark:text-gray-300">{{ $this->record->payer_name }}</span>
                    </p>
                    @if ($this->record->payer_phone_number)
                        <p class="text-gray-600 dark:text-gray-400">
                            Phone: <span
                                class="text-gray-900 dark:text-gray-300">{{ $this->record->payer_phone_number }}</span>
                        </p>
                    @endif
                </div>
            </div>

            <!-- Terms -->
            <div class="px-4 sm:px-6 md:px-8 py-4 sm:py-6">
                <h3 class="font-medium text-xs sm:text-sm text-gray-900 dark:text-gray-300 mb-2 sm:mb-3">Terms & Notes:
                </h3>
                <ul class="list-disc list-inside space-y-1 text-xs sm:text-sm text-gray-600 dark:text-gray-400">
                    <li>Payment is non-refundable.</li>
                    <li>Please keep this receipt for your records.</li>
                    @if ($this->record->remark)
                        <li>{{ $this->record->remark }}</li>
                    @endif
                </ul>
            </div>

            <!-- Footer -->
            <div class="px-4 sm:px-6 md:px-8 py-4 bg-gray-50/50 dark:bg-gray-800/50 text-center">
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">
                    This is a computer-generated receipt and does not require a signature.
                </p>
                <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400">
                    Generated on {{ Carbon\Carbon::now()->format('F j, Y g:i A') }}
                </p>
            </div>
        </div>
    </div>
</x-filament-panels::page>

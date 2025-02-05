<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $payment->reference }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .receipt-container {
            letter-spacing: -0.01em;
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>

<body class="bg-white">
    <!-- Rest of your existing code stays exactly the same -->
    <div class="mt-12 mx-auto p-5">
        <div class="bg-white receipt-container">
            <!-- Header -->
            <div class="px-6 pt-6">
                <div class="flex flex-col sm:flex-row justify-between items-center sm:items-start">
                    <!-- School Info -->
                    <div class="text-center sm:text-left w-full sm:w-auto">
                        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-4 mb-4">
                            @if (isset($logoData))
                                <div class="w-16 h-16">
                                    <img src="{{ $logoData }}" alt="{{ $payment->school->name }}"
                                        class="w-full h-full object-contain">
                                </div>
                            @else
                                <div class="text-xl font-bold">
                                    {{ substr($payment->school->name, 0, 2) }}
                                </div>
                            @endif
                            <div>
                                <div class="flex flex-col sm:flex-row items-center sm:items-center gap-2 sm:gap-4">
                                    <h1 class="text-[15px] font-semibold text-gray-900">{{ $payment->school->name }}
                                    </h1>
                                    <span class="text-xs font-medium text-blue-600">PAYMENT RECEIPT</span>
                                </div>
                                <div class="mt-2 text-[13px] space-y-0.5 text-gray-600">
                                    <p>{{ $payment->school->address }}</p>
                                    <p>Tel: {{ $payment->school->phone }}</p>
                                    <p>Email: {{ $payment->school->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Info -->
                    <div class="text-center sm:text-right w-full sm:w-auto mt-4 sm:mt-0">
                        <div class="mb-3 hidden sm:block">
                            <div class="w-20 h-20 ml-auto border rounded flex items-center justify-center">
                                @php
                                    $qrCode = SimpleSoftwareIO\QrCode\Facades\QrCode::size(72)->generate($QrViewUrl);
                                @endphp
                                {!! $qrCode !!}
                            </div>
                        </div>
                        <div class="text-[13px] space-y-1">
                            <p>Reference: <span class="text-gray-700">{{ $payment->reference }}</span></p>
                            <p>Date: <span
                                    class="text-gray-700">{{ Carbon\Carbon::parse($payment->paid_at)->format('F j, Y') }}</span>
                            </p>
                            <p>Status: <span class="text-green-600">{{ $payment->status?->name }}</span></p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($payment->is_balance_payment)
                <div class="px-6 py-4 mt-4 bg-blue-50">
                    <div class="grid grid-cols-2 gap-x-4">
                        <div class="space-y-2">
                            <p class="text-[13px] text-gray-600">Original Payment Reference:
                                <span
                                    class="font-medium text-gray-700">{{ $payment->originalPayment->reference }}</span>
                            </p>
                            <p class="text-[13px] text-gray-600">Original Payment Date:
                                <span
                                    class="font-medium text-gray-700">{{ Carbon\Carbon::parse($payment->originalPayment->paid_at)->format('M j, Y') }}</span>
                            </p>
                        </div>
                        <div class="space-y-2">
                            <p class="text-[13px] text-gray-600">Original Amount:
                                <span
                                    class="font-medium text-gray-700">{{ formatNaira($payment->originalPayment->amount) }}</span>
                            </p>
                            <p class="text-[13px] text-gray-600">Previous Payment:
                                <span
                                    class="font-medium text-gray-700">{{ formatNaira($payment->originalPayment->deposit - $payment->deposit) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Student Details -->
            <div class="px-6 py-4 mt-6 bg-gray-50">
                <div class="grid grid-cols-2 gap-x-12">
                    <div class="space-y-2">
                        <p class="text-[13px]">Student: <span
                                class="font-medium text-gray-700">{{ $payment->student->full_name }}</span></p>
                        <p class="text-[13px]">Class: <span
                                class="font-medium text-gray-700">{{ $payment->student->classRoom?->name }}</span></p>
                        <p class="text-[13px]">Due Date: <span
                                class="font-medium text-gray-700">{{ Carbon\Carbon::parse($payment->due_date)->format('M j, Y') }}</span>
                        </p>
                    </div>
                    <div class="space-y-2">
                        <p class="text-[13px]">Session: <span
                                class="font-medium text-gray-700">{{ $payment->academicSession?->name }}</span></p>
                        <p class="text-[13px]">Term: <span
                                class="font-medium text-gray-700">{{ $payment->term?->name }}</span></p>
                        <p class="text-[13px]">Paid Date: <span
                                class="font-medium text-gray-700">{{ Carbon\Carbon::parse($payment->paid_at)->format('M j, Y') }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modified Payment Details -->
            <div class="px-6 py-6">
                <div
                    class="grid grid-cols-[2fr,1fr,1.2fr,0.8fr,1.2fr,1.2fr,1.2fr] gap-3 text-[13px] font-medium text-gray-700 pb-2 border-b">
                    <div>Payment For</div>
                    <div>Plan</div>
                    <div>Method</div>
                    <div class="text-center">Qty</div>
                    <div class="text-right">Amount</div>
                    <div class="text-right">Paid</div>
                    <div class="text-right">Balance</div>
                </div>

                <!-- Payment Items -->
                @foreach ($payment->paymentItems as $item)
                    <div
                        class="grid grid-cols-[2fr,1fr,1.2fr,0.8fr,1.2fr,1.2fr,1.2fr] gap-3 text-[13px] py-3 border-b last:border-b-0">
                        <div class="text-gray-600">{{ $item->paymentType?->name }}</div>
                        <div class="text-gray-600">
                            {{ $item->is_tuition ? ucfirst($payment->payment_plan_type ?? 'N/A') : '-' }}
                        </div>
                        <div class="text-gray-600">{{ $payment->paymentMethod?->name }}</div>
                        <div class="text-center text-gray-600">
                            {{ $item->paymentType?->category === 'physical_item' ? $item->quantity : '-' }}
                        </div>
                        <div class="text-right font-medium text-gray-900">{{ formatNaira($item->amount) }}</div>
                        <div class="text-right font-medium text-green-600">{{ formatNaira($item->deposit) }}</div>
                        <div class="text-right">
                            <span @class([
                                'font-medium',
                                'text-gray-900' => $item->balance == 0,
                                'text-red-600' => $item->balance > 0,
                            ])>
                                {{ formatNaira($item->balance) }}
                            </span>
                        </div>
                    </div>
                @endforeach


                <!-- Modified Summary -->
                <div class="mt-6 ml-auto max-w-xs">
                    <div class="space-y-2 text-[13px]">
                        <div class="pt-2 border-t">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Total Amount</span>
                                <span class="text-gray-900">{{ formatNaira($payment->amount) }}</span>
                            </div>
                            <div class="flex justify-between mt-2">
                                <span class="text-gray-600">Total Paid</span>
                                <span class="font-medium text-green-600">{{ formatNaira($payment->deposit) }}</span>
                            </div>
                            <div class="flex justify-between mt-2 pt-2 border-t">
                                <span class="text-gray-600 font-medium">Total Balance</span>
                                <span @class([
                                    'font-bold',
                                    'text-gray-900' => $payment->balance == 0,
                                    'text-red-600' => $payment->balance > 0,
                                ])>{{ formatNaira($payment->balance) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Terms & Notes -->
            <div class="px-6 py-4 bg-gray-50">
                @if ($payment->shouldShowTerms())
                    <h3 class="text-[13px] font-medium text-gray-900 mb-2">Terms & Notes:</h3>
                    <ul class="list-disc list-inside space-y-1 text-[13px] text-gray-600">
                        @foreach ($payment->getTerms() as $term)
                            <li>{{ $term }}</li>
                        @endforeach
                        @if ($payment->remark)
                            <li>{{ $payment->remark }}</li>
                        @endif
                    </ul>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 text-center">
                <p class="text-[11px] text-gray-500">This is a computer-generated receipt and does not require a
                    signature</p>
                <p class="text-[11px] text-gray-500">Generated on {{ Carbon\Carbon::now()->format('F j, Y g:i A') }}
                </p>
            </div>
        </div>
    </div>
</body>

</html>

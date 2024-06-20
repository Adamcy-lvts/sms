<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Receipt</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.15/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">

    <div class="bg-white dark:bg-gray-800 p-8 md:p-12 lg:p-16 rounded-lg mt-20 mx-auto max-w-4xl">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="font-bold text-xl text-gray-900 dark:text-gray-100 mb-2">Receipt</h1>
                <p class="text-gray-700 dark:text-gray-300 text-xs mb-1">Receipt number: <span class="font-semibold">{{ $receipt->receipt_number }}</span></p>
                <p class="text-gray-700 dark:text-gray-300 text-xs mb-1">Date paid: <span class="font-semibold">{{ formatDate($receipt->payment_date) }}</span></p>
                <p class="text-gray-700 dark:text-gray-300 text-xs mb-1">Payment method: <span class="font-semibold">{{ $payment->method ?? 'N/A' }}</span></p>
            </div>
            <div>
                <!-- Insert high-resolution company logo here -->
                <img src="/path-to-your-logo.png" alt="Company Logo" class="h-10 w-auto">
            </div>
        </div>

        <div class="mt-6 text-xs">
            <div class="flex justify-between mb-4">
                <div class="text-gray-700 dark:text-gray-200">
                    <p class="font-semibold text-md">Devcentric Studio</p>
                    <p class="mb-1">123 Studio Address</p>
                    <p class="mb-1">New York, NY, 10001</p>
                    <p class="mb-1">USA</p>
                </div>
                <div class="text-gray-700 dark:text-gray-200">
                    <p class="font-semibold text-md">Bill to</p>
                    <p class="mb-1">{{ $receipt->school->name }}</p>
                    <p class="mb-1">{{ $receipt->school->email }}</p>
                    <p class="mb-1">{{ $receipt->school->phone }}</p>
                </div>
            </div>

            <div class="text-md font-bold text-gray-900 dark:text-gray-100 mb-4">
                {{ formatNaira($payment->amount) }} paid on <span>{{ formatDate($receipt->payment_date) }}</span>
            </div>

            <div class="w-full bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">Description</span>
                    <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">Qty</span>
                    <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">Unit price</span>
                    <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">Amount</span>
                </div>
                <div class="border-t border-gray-300 dark:border-gray-600 pt-2">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">{{ $payment->subscription->plan->name }}</span>
                        <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">1</span>
                        <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">{{ formatNaira($payment->amount) }}</span>
                        <span class="text-gray-700 mb-1 text-xs dark:text-gray-200">{{ formatNaira($receipt->amount) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-between mt-6 text-gray-900 dark:text-gray-100">
                <div>
                    <p class="mb-1 text-xs">Subtotal</p>
                    <p class="mb-1 text-xs">Total</p>
                    <p class="mb-1 text-xs">Amount paid</p>
                </div>
                <div class="text-right">
                    <p class="mb-1 text-xs">{{ formatNaira($receipt->amount) }}</p>
                    <p class="mb-1 text-xs">{{ formatNaira($receipt->amount) }}</p>
                    <p class="mb-1 text-xs">{{ formatNaira($payment->amount) }}</p>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

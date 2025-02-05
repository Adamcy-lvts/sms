<?php

namespace App\Http\Controllers\Sms;

use App\Models\School;
use App\Models\Payment;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\Browsershot\Browsershot;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use App\Filament\Sms\Resources\PaymentResource;

class PaymentReceiptController extends Controller
{
    /**
     * Display the receipt for printing
     *
     * @param School $tenant
     * @param Payment $payment
     * @return \Illuminate\View\View
     */
    public function print(Payment $payment, School $tenant)
    {
        // Ensure the payment belongs to the current tenant
        if ($payment->school_id !== $tenant->id) {
            abort(Response::HTTP_NOT_FOUND);
        }
        $QrViewUrl = PaymentResource::getUrl('view', [
            'record' => $payment->id,
            'tenant' => $tenant->id,
        ]);
        
        return view('pdfs.student-payment-receipt', [
            'payment' => $payment->load([
                'student.classRoom',
                'academicSession',
                'term',
                'status',
                'paymentType',
                'paymentMethod',
                'QrViewUrl' => $QrViewUrl,
            ]),
            'school' => $tenant,
            'isPrintMode' => true
        ]);
    }


    /**
     * Download the receipt as PDF
     *
     * @param School $tenant
     * @param Payment $payment
     * @return mixed
     */
    public function download(School $tenant, Payment $payment)
    {
        if ($payment->school_id !== $tenant->id) {
            abort(Response::HTTP_NOT_FOUND);
        }

        try {
            $fileName = str("{$tenant->name}-receipt-{$payment->reference}")
                ->slug()
                ->append('.pdf');

            return Pdf::view('sms.payments.receipt', [
                'payment' => $payment->load([
                    'student.classRoom',
                    'academicSession',
                    'term',
                    'status',
                    'paymentType',
                    'paymentMethod'
                ]),
                'school' => $tenant,
                'isPrintMode' => false
            ])
                ->format('a4')
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $browsershot->setChromePath(config('app.chrome_path'))
                        ->margins(20, 20, 20, 20)
                        ->format('A4')
                        ->showBackground()
                        ->waitUntilNetworkIdle();
                })
                ->download($fileName);
        } catch (\Exception $e) {
            report($e);

            return back()->with('error', 'Error generating receipt PDF.');
        }
    }

    /**
     * Get the base receipt view for both print and download
     *
     * @param School $tenant
     * @param Payment $payment
     * @param bool $isPrintMode
     * @return \Illuminate\View\View
     */
    protected function getReceiptView(School $tenant, Payment $payment, bool $isPrintMode = false): View
    {
        return view('sms.payments.receipt', [
            'payment' => $payment->load([
                'student.classRoom',
                'academicSession',
                'term',
                'status',
                'paymentType',
                'paymentMethod'
            ]),
            'school' => $tenant,
            'isPrintMode' => $isPrintMode
        ]);
    }
}

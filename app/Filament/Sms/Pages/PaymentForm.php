<?php

namespace App\Filament\Sms\Pages;

use App\Models\Plan;
use App\Models\Agent;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\SubsPayment;
use Filament\Support\RawJs;
use App\Models\PaymentMethod;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Redirect;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Unicodeveloper\Paystack\Facades\Paystack;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class PaymentForm extends Page
{
    use HasPageShield;
    
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.sms.pages.payment-form';
    protected static bool $shouldRegisterNavigation = false;

    public $plan;
    public $school_name;
    public $email;
    public $amount;
    public $reference;
    public $price;
    public $payment;
    public $receipt;
    public $user;
    public $proof_of_payment;
    public $payment_method = 'card'; // Default to 'card' payment method
    public $tenant;

    /**
     * Convert string price to float/integer
     */
    protected function convertPrice($price): float
    {
        if (is_string($price)) {
            // Remove currency symbol, commas and convert to float
            return (float) preg_replace('/[^0-9.]/', '', $price);
        }
        return (float) $price;
    }

    public function mount()
    {
        $this->plan = Plan::findOrFail(request()->query('id'));

        // Check if it's annual billing and has discounted price
        $isAnnual = request()->query('billing') === 'annual';
        $amount = $isAnnual && $this->plan->discounted_price !== null ?
            $this->plan->discounted_price :
            $this->plan->price;

        $this->price = formatNaira($this->convertPrice($amount));
        $this->tenant = Filament::getTenant();
        $this->user = auth()->user();

        $this->form->fill([
            'school_name' => $this->tenant->name,
            'email' => $this->tenant->email,
            'amount' => $this->price,
            'payment_method' => $this->payment_method,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make($this->plan->title)
                    ->description("You are about to subscribe to the {$this->plan->name} plan for {$this->price}.  This plan will be valid for {$this->plan->duration} days.")
                    ->schema([
                        Radio::make('payment_method')
                            ->label('Select Payment Method')
                            ->options([
                                'card' => 'Pay with Card (Instant activation)',
                                'bank_transfer' => 'Pay via Bank Transfer (24-48 hours activation)',
                            ])
                            ->default('card')
                            ->reactive()
                            ->required()
                            ->helperText(function ($state) {
                                if ($state === 'bank_transfer') {
                                    return 'Note: Bank transfer payments require verification before activation (24-48 hours)';
                                }
                                return 'Card payments are processed instantly';
                            }),
                        TextInput::make('school_name')->disabled()->required(),
                        TextInput::make('email')->disabled()->required(),
                        TextInput::make('amount')
                            ->prefix('₦')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->disabled(),
                        Placeholder::make('bank_details')
                            ->label('Bank Transfer Details')
                            ->content(new HtmlString('
                                <div class="text-sm">
                                    <p><strong>Bank:</strong> GTBank</p>
                                    <p><strong>Account Name:</strong> Adamu Mohammed</p>
                                    <p><strong>Account Number:</strong> 0172791950</p>
                                    <p class="mt-2">After payment, please upload your proof of payment below.</p>
                                </div>
                            '))
                            ->visible(fn(callable $get) => $get('payment_method') === 'bank_transfer'),
                        FileUpload::make('proof_of_payment')
                            ->label('Proof of Payment')
                            ->image()
                            ->acceptedFileTypes(['image/*', 'application/pdf'])
                            ->directory('payment_proof')
                            ->required(fn(callable $get) => $get('payment_method') === 'bank_transfer')
                            ->maxSize(5120) // 5MB max
                            ->helperText('Upload a screenshot, photo, or PDF of your payment confirmation (max 5MB)')
                            ->visible(fn(callable $get) => $get('payment_method') === 'bank_transfer')
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $set('upload_complete', true);
                                }
                            }),
                        Placeholder::make('upload_status')
                            ->content(fn(callable $get) => $get('upload_complete') ? 'Upload complete!' : 'Waiting for file...')
                            ->visible(fn(callable $get) => $get('payment_method') === 'bank_transfer'),
                        Placeholder::make('bank_transfer_notice')
                            ->label('Important Notice')
                            ->content(new HtmlString('
                                <div class="text-sm space-y-2">
                                    <p class="font-medium text-warning-600 dark:text-warning-400">⚠️ Please Note:</p>
                                    <ul class="list-disc list-inside space-y-1 text-gray-600 dark:text-gray-400">
                                        <li>Your subscription will not be activated immediately.</li>
                                        <li>We need to verify your payment first (usually takes 24-48 hours).</li>
                                        <li>Make sure to upload clear proof of payment to speed up verification.</li>
                                        <li>You will receive an email notification once your subscription is activated.</li>
                                    </ul>
                                </div>
                            '))
                            ->visible(fn(callable $get) => $get('payment_method') === 'bank_transfer'),
                    ])
            ]);
    }

    public function redirectToGateway()
    {
        $tenant = Filament::getTenant();
        $agent = $tenant->agent;
        $splitData = null;

        // Only process split payment if agent exists and has subaccount code
        if ($agent && $agent->subaccount_code) {
            Log::info('Processing payment with agent split', [
                'agent_id' => $agent->id,
                'percentage' => $agent->percentage
            ]);

            $splitData = [
                "type" => "percentage",
                "currency" => "NGN",
                "subaccounts" => [[
                    "subaccount" => $agent->subaccount_code,
                    "share" => $agent->percentage
                ]],
                "bearer_type" => "account",
                "main_account_share" => 100 - $agent->percentage
            ];
        }

        $data = [
            'amount' => $this->convertPrice($this->price) * 100,
            'email' => $tenant->email,
            'reference' => Paystack::genTranxRef(),
            'metadata' => [
                'planId' => $this->plan->id,
                'schoolId' => $tenant->id,
                'schoolSlug' => $tenant->slug,
                'agentId' => $agent?->id,
            ],
            'split' => $splitData ? json_encode($splitData) : null
        ];

        try {
            Log::info('Initiating Paystack payment', [
                'amount' => $this->price,
                'school' => $tenant->name,
                'plan' => $this->plan->name,
                'has_split' => !is_null($splitData)
            ]);

            $response = Paystack::getAuthorizationUrl($data)->redirectNow();
            return $response;
        } catch (\Exception $e) {
            Log::error('Payment initialization failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Redirect::back()->withErrors('Failed to initiate payment. Please try again.');
        }
    }

    public function processPayment()
    {
        $data = $this->form->getState();
        $tenant = Filament::getTenant();

        if ($data['payment_method'] === 'card') {
            return $this->redirectToGateway();
        }

        if ($data['payment_method'] === 'bank_transfer') {

            $paymentMethod = PaymentMethod::where('slug', 'bank-transfer')->first();
            $this->payment = SubsPayment::create([
                'school_id' => $tenant->id,
                'plan_id' => $this->plan->id,
                'amount' => $this->convertPrice($this->price),
                'status' => 'pending',
                'payment_method_id' => $paymentMethod->id,
                'payment_for' => 'subscription plan',
                'payment_date' => now(),
                'proof_of_payment' => $data['proof_of_payment'],
            ]);

            Notification::make()
                ->title('Payment Received')
                ->body('Your bank transfer payment has been received and is pending confirmation. We will review and activate your subscription shortly.')
                ->success()
                ->send();

            return $this->redirectRoute('filament.sms.tenant', ['tenant' => $tenant->slug]);
        }

        return Redirect::back()->withErrors('Unsupported payment method selected.');
    }
}

<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Illuminate\Database\Eloquent\Collection;

use App\Models\Plan;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Models\SubscriptionReceipt;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\SubsPaymentResource\Pages;
use App\Filament\Resources\SubsPaymentResource\RelationManagers;

class SubsPaymentResource extends Resource
{
    protected static ?string $model = SubsPayment::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Subscription Payments';

    public static function getModelLabel(): string
    {
        return 'Subscription Payment';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->schema([
                    // Payment Information Section
                    Section::make('Payment Information')
                        ->description('Basic payment details')
                        ->schema([
                            Select::make('school_id')
                                ->relationship('school', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->reactive(),

                            Select::make('plan_id')
                            ->options(Plan::all()->pluck('name', 'id')->toArray())
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $plan = Plan::find($state);
                                        if ($plan) {
                                            $set('amount', $plan->price);
                                        }
                                    }
                                }),

                            TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->prefix('₦')
                                ->disabled()
                                ->dehydrated(),

                            Select::make('payment_method_id')
                                ->relationship('paymentMethod', 'name')
                                ->required(),

                            TextInput::make('reference')
                                ->default(fn() => 'PAY-' . strtoupper(uniqid()))
                                ->disabled()
                                ->dehydrated(),

                            DatePicker::make('payment_date')
                                ->required()
                                ->default(now()),
                        ])->columns(2),

                    // Commission & Split Section
                    Section::make('Commission Details')
                        ->description('Agent commission and split information')
                        ->schema([
                            Select::make('agent_id')
                                ->relationship('agent', 'business_name')
                                ->searchable()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    if ($state && $get('amount')) {
                                        $agent = \App\Models\Agent::find($state);
                                        if ($agent) {
                                            $splitAmount = ($get('amount') * $agent->percentage) / 100;
                                            $set('split_amount_agent', $splitAmount);
                                            $set('net_amount', $get('amount') - $splitAmount);
                                        }
                                    }
                                }),

                            TextInput::make('split_amount_agent')
                                ->label('Agent Commission')
                                ->numeric()
                                ->prefix('₦')
                                ->disabled(),

                            TextInput::make('net_amount')
                                ->label('Net Amount')
                                ->numeric()
                                ->prefix('₦')
                                ->disabled(),

                            TextInput::make('split_code')
                                ->disabled()
                                ->dehydrated(),
                        ])->columns(2)
                        ->hidden(fn($record) => !$record?->agent_id),
                ])->columnSpan(['lg' => 2]),

            Group::make()
                ->schema([
                    // Payment Status Section
                    Section::make('Payment Status')
                        ->schema([
                            Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'paid' => 'Paid',
                                    'failed' => 'Failed',
                                    'refunded' => 'Refunded',
                                ])
                                ->required()
                                ->default('pending'),

                            FileUpload::make('proof_of_payment')
                                ->directory('payment-proofs')
                                ->preserveFilenames()
                                ->acceptedFileTypes(['application/pdf', 'image/*'])
                                ->maxSize(5120),
                        ]),

                    // Summary Section (for edit form)
                    // Section::make('Payment Summary')
                    //     ->schema([
                    //         ViewField::make('receipt')
                    //             ->view('filament.components.payment-receipt-summary')
                    //             ->visible(fn($record) => $record && $record->subscriptionReceipt),
                    //     ])
                    //     ->visible(fn($record) => $record !== null),
                ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable(),

                TextColumn::make('net_amount')->money('NGN')->label('Net Amount'),
                TextColumn::make('split_amount_agent')->label('Agent Fee')->money('NGN'),
                TextColumn::make('split_code')->label('Split Code'),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'failed' => 'danger',
                        'refunded' => 'info',
                        default => 'secondary',
                    }),

                TextColumn::make('agent.business_name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('reference')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                IconColumn::make('proof_of_payment')
                    ->label('Proof')
                    ->icon('heroicon-o-document-arrow-down')
                    // ->boolean()
                    ->action(function ($record) {
                        if (!$record->proof_of_payment || !Storage::disk('public')->exists($record->proof_of_payment)) {
                            Notification::make()
                                ->danger()
                                ->title('File not found')
                                ->send();
                            return;
                        }

                        return response()->download(
                            Storage::disk('public')->path($record->proof_of_payment)
                        );
                    }),

                TextColumn::make('payment_date')
                    ->dateTime('F j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'failed' => 'Failed',
                        'refunded' => 'Refunded',
                    ]),

                SelectFilter::make('school')
                    ->relationship('school', 'name'),

                SelectFilter::make('plan')
                    ->relationship('plan', 'name'),

                SelectFilter::make('agent')
                    ->relationship('agent', 'business_name'),
            ])
            ->actions([
                Action::make('confirm_payment')
                    ->label('Confirm Payment')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Payment Status')
                    ->modalDescription('Are you sure you want to update this payment? This will affect the school\'s subscription status.')
                    ->modalSubmitActionLabel('Yes, update payment')
                    ->color('warning')
                    ->action(function (SubsPayment $record) {
                        DB::transaction(function () use ($record) {
                            try {
                                // Update payment status
                                $record->update(['status' => 'paid']);

                                // Handle subscription
                                $school = $record->school;
                                $plan = $record->plan;

                                // Cancel existing subscription if any
                                if ($currentSub = $school->currentSubscription()) {
                                    $currentSub->update([
                                        'status' => 'cancelled',
                                        'cancelled_at' => now(),
                                    ]);
                                }

                                // Create new subscription
                                $subscription = $school->subscriptions()->create([
                                    'plan_id' => $plan->id,
                                    'status' => 'active',
                                    'starts_at' => now(),
                                    'ends_at' => now()->addDays($plan->duration),
                                ]);

                                $receipt = $record->subscriptionReceipt()->create([
                                    'payment_id' => $record->id,
                                    'school_id' => $school->id,
                                    'payment_date' => now(),
                                    'receipt_for' => 'subscription',
                                    'amount' => $record->amount,
                                    'receipt_number' => SubscriptionReceipt::generateReceiptNumber(now()),
                                ]);

                                // Send receipt email
                                try {
                                    Log::info('Attempting to send receipt email');
                                    $record->sendReceiptByEmail($receipt, $record, $subscription);
                                } catch (\Exception $e) {
                                    Log::error('Failed to send receipt email', [
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString()
                                    ]);
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Payment confirmed successfully')
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Payment confirmation failed', [
                                    'error' => $e->getMessage(),
                                    'payment_id' => $record->id,
                                ]);
                                throw $e;
                            }
                        });
                    }),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => in_array($record->status, ['pending', 'failed'])),
                ])

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if (in_array($record->status, ['pending', 'failed'])) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ReceiptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubsPayments::route('/'),
            'create' => Pages\CreateSubsPayment::route('/create'),
            'edit' => Pages\EditSubsPayment::route('/{record}/edit'),
            // 'view' => Pages\ViewSubsPayment::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'school.name', 'plan.name', 'agent.business_name'];
    }
}

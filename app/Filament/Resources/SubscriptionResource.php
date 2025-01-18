<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Plan;
use Filament\Tables;
use App\Models\School;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Subscription;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Filament\Resources\SubscriptionResource\RelationManagers;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->schema([
                    Section::make('Subscription Details')
                        ->description('Basic subscription information')
                        ->schema([
                            Select::make('school_id')
                                ->label('School')
                                ->relationship('school', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')
                                        ->required(),
                                    TextInput::make('email')
                                        ->email()
                                        ->required(),
                                ]),

                            Select::make('plan_id')
                                ->label('Plan')
                                ->relationship('plan', 'name')
                                ->required()
                                ->preload()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state) {
                                        $plan = Plan::find($state);
                                        if ($plan) {
                                            $startDate = now();
                                            $set('starts_at', $startDate);
                                            $set('ends_at', $startDate->copy()->addDays($plan->duration));
                                        }
                                    }
                                }),

                            Select::make('status')
                                ->options([
                                    'active' => 'Active',
                                    'pending' => 'Pending',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                    'suspended' => 'Suspended'
                                ])
                                ->required()
                                ->default('active'),

                            TextInput::make('subscription_code')
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Auto-generated')
                                ->disabled()
                                ->dehydrated(),
                        ])->columns(2),

                    Section::make('Subscription Period')
                        ->schema([
                            DatePicker::make('starts_at')
                                ->label('Start Date')
                                ->required()
                                ->default(now()),

                            DatePicker::make('ends_at')
                                ->label('End Date')
                                ->required()
                                ->after('starts_at')
                                ->default(now()->addMonth()),

                            DatePicker::make('cancelled_at')
                                ->label('Cancellation Date')
                                ->after('starts_at')
                                ->before('ends_at')
                                ->visible(fn ($get) => $get('status') === 'cancelled'),

                            DatePicker::make('next_payment_date')
                                ->label('Next Payment Due')
                                ->after('starts_at')
                                ->before('ends_at')
                                ->visible(fn ($get) => $get('is_recurring')),
                        ])->columns(2),
                ])->columnSpan(['lg' => 2]),

            Group::make()
                ->schema([
                    Section::make('Subscription Settings')
                        ->schema([
                            Toggle::make('is_recurring')
                                ->label('Recurring Subscription')
                                ->default(true)
                                ->helperText('Enable automatic renewal'),

                            Toggle::make('is_on_trial')
                                ->label('Trial Period')
                                ->default(false)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, $get) {
                                    if ($state) {
                                        $plan = Plan::find($get('plan_id'));
                                        if ($plan) {
                                            $set('trial_ends_at', now()->addDays($plan->trial_period ?? 30));
                                        }
                                    } else {
                                        $set('trial_ends_at', null);
                                    }
                                }),

                            DatePicker::make('trial_ends_at')
                                ->label('Trial End Date')
                                ->after('starts_at')
                                ->before('ends_at')
                                ->visible(fn ($get) => $get('is_on_trial')),

                            TextInput::make('token')
                                ->maxLength(255)
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record && $record->token),
                        ]),

                    Section::make('Summary')
                        ->schema([
                            Placeholder::make('total_payments')
                                ->label('Total Payments')
                                ->content(fn ($record) => $record ? 
                                    formatNaira($record->payments()->sum('amount')) : 
                                    'N/A'
                                ),

                            Placeholder::make('payment_status')
                                ->label('Payment Status')
                                ->content(fn ($record) => $record ? 
                                    ucfirst($record->getPaymentStatus()) : 
                                    'N/A'
                                ),
                        ])
                        ->visible(fn ($record) => $record !== null),
                ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('school.name')
                    ->label('School')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'expired' => 'danger',
                        'cancelled' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn($record) => $record->is_on_trial ? 'Trial' : $record->status),

                TextColumn::make('subscription_code')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),

                TextColumn::make('is_recurring')
                    ->badge()
                    ->color(fn ($record) => $record->is_recurring ? 'success' : 'gray')
                    ->label('Recurring')
                    ->state(fn ($record) => $record->is_recurring ? 'Yes' : 'No'),

                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total_paid')
                    ->money('NGN')
                    ->state(fn ($record) => $record->payments()->sum('amount'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ]),

                SelectFilter::make('plan')
                    ->relationship('plan', 'name'),

                SelectFilter::make('school')
                    ->relationship('school', 'name'),

                Tables\Filters\TernaryFilter::make('is_recurring')
                    ->label('Recurring Status'),

                Tables\Filters\TernaryFilter::make('is_on_trial')
                    ->label('Trial Status'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'active')
                    ->action(function (Subscription $record) {
                        DB::transaction(function () use ($record) {
                            $record->update([
                                'status' => 'cancelled',
                                'cancelled_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Subscription Cancelled')
                                ->success()
                                ->send();
                        });
                    }),

                Action::make('renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['expired', 'cancelled']))
                    ->action(function (Subscription $record) {
                        DB::transaction(function () use ($record) {
                            $startDate = now();
                            $record->update([
                                'status' => 'active',
                                'starts_at' => $startDate,
                                'ends_at' => $startDate->copy()->addDays($record->plan->duration),
                                'cancelled_at' => null,
                            ]);

                            Notification::make()
                                ->title('Subscription Renewed')
                                ->success()
                                ->send();
                        });
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->status !== 'active') {
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
            // RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
            // 'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'active')->count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['subscription_code', 'school.name', 'plan.name'];
    }
}
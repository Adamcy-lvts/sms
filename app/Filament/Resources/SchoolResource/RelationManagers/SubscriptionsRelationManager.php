<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use App\Models\Plan;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'subscriptions';
    protected static ?string $title = 'Subscription History';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('plan_id')
                ->relationship('plan', 'name')
                ->required()
                ->preload()
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set) {
                    if ($state) {
                        $plan = Plan::find($state);
                        $set('amount', $plan->price);
                        $set('ends_at', now()->addDays($plan->duration));
                    }
                }),

            TextInput::make('amount')
                ->label('Amount (₦)')
                ->disabled()
                ->numeric(),

            DatePicker::make('starts_at')
                ->label('Start Date')
                ->default(now())
                ->required(),

            DatePicker::make('ends_at')
                ->label('End Date')
                ->required(),

            Toggle::make('is_trial')
                ->label('Trial Period')
                ->default(false)
                ->reactive()
                ->afterStateUpdated(function ($state, callable $set, $get) {
                    if ($state) {
                        $plan = Plan::find($get('plan_id'));
                        if ($plan) {
                            $set('trial_ends_at', now()->addDays($plan->trial_period));
                        }
                    } else {
                        $set('trial_ends_at', null);
                    }
                }),

            DatePicker::make('trial_ends_at')
                ->label('Trial End Date')
                ->visible(fn($get) => $get('is_trial')),

            Select::make('status')
                ->options([
                    'active' => 'Active',
                    'cancelled' => 'Cancelled',
                    'expired' => 'Expired',
                    'suspended' => 'Suspended',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('plan.name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'expired' => 'warning',
                        'suspended' => 'gray',
                        default => 'secondary',
                    }),

                TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('is_trial')
                    ->label('Trial')
                    ->badge()
                    ->color('warning')
                    ->state(fn($record) => $record->is_trial ? 'Trial' : 'Regular'),

                TextColumn::make('payments_sum_amount')
                    ->sum('payments', 'amount')
                    ->label('Total Paid')
                    ->money('NGN')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'cancelled' => 'Cancelled',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                    ]),

                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),

                Tables\Filters\TernaryFilter::make('is_trial')
                    ->label('Trial Status')
                    ->placeholder('All Subscriptions')
                    ->trueLabel('Trial Only')
                    ->falseLabel('Regular Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record) {
                        // Create payment record if not a trial
                        if (!$record->is_trial) {
                            SubsPayment::create([
                                'school_id' => $record->school_id,
                                'amount' => $record->amount,
                                'status' => 'paid',
                                'payment_date' => now(),
                                'subscription_id' => $record->id,
                            ]);
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        if ($record->status === 'active' && !$record->is_trial) {
                            $this->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            if ($records->contains(
                                fn($record) =>
                                $record->status === 'active' && !$record->is_trial
                            )) {
                                $this->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}

<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use App\Models\Agent;
use App\Models\Status;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Helpers\PaystackHelper;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use App\Filament\Resources\AgentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AgentResource\RelationManagers;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required(),
                Forms\Components\TextInput::make('last_name')
                    ->required(),
                Forms\Components\TextInput::make('email'),
                Forms\Components\TextInput::make('phone')
                    ->required(),
                Forms\Components\TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('bank_id')->options(Bank::all()->pluck('name', 'id')->toArray())
                    ->required(),
                // Forms\Components\Select::make('status_id')->options(Status::all()->pluck('name', 'id')->toArray())->default('active')->label('Status'),
                Select::make('status_id')
                    ->relationship(name: 'user.status', titleAttribute: 'name')->label('Status')->default('active'),

                Forms\Components\TextInput::make('referral_code')->disabled(),
                Forms\Components\TextInput::make('subaccount_code')
                    ->disabled(),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->default(20.00),
                Forms\Components\TextInput::make('fixed_rate')
                    ->numeric()
                    ->default(500.00),
                Forms\Components\TextInput::make('password')
                    ->label(__('filament-panels::pages/auth/register.form.password.label'))
                    ->password()
                    ->rule(Password::default())
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->same('passwordConfirmation')
                    ->validationAttribute(__('filament-panels::pages/auth/register.form.password.validation_attribute')),
                Forms\Components\TextInput::make('passwordConfirmation')
                    ->label(__('filament-panels::pages/auth/register.form.password_confirmation.label'))
                    ->password()
                    ->dehydrated(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Name')
                    ->sortable(),
                TextColumn::make('user.status.name')->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'blocked' => 'danger',
                    }),
                TextColumn::make('user.email')->label('Email')->copyable()
                    ->searchable(),
                TextColumn::make('user.phone')->label('Phone')->copyable()
                    ->searchable(),
                TextColumn::make('business_name')
                    ->searchable(),
                TextColumn::make('account_number')->copyable()
                    ->searchable(),
                TextColumn::make('account_name')
                    ->searchable(),
                TextColumn::make('bank.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('referral_code')
                    ->searchable(),
                TextColumn::make('subaccount_code')
                    ->searchable(),
                TextColumn::make('percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fixed_rate')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')->label('Joined On')
                    ->dateTime()

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('createSubaccount')
                    ->label('Create Subaccount')
                    ->action(function ($record) {
                        try {
                            // dd($record->business_name);
                            // Prepare subaccount data
                            $subaccountData = [
                                'business_name' => $record->business_name,
                                'settlement_bank' => $record->bank->code, // Ensure the bank has a 'code' field
                                'account_number' => $record->account_number,
                                'percentage_charge' => $record->percentage, // Ensure this field exists on your model
                                'primary_contact_email' => $record->user->email,
                            ];

                            Log::info($subaccountData);
                            // dd($subaccountData);
                            // Attempt to create a subaccount on Paystack
                            $subaccount = PaystackHelper::createSubAccount($subaccountData);
                            // Paystack::createSubAccount($subaccountData);
                            // Check if the creation was successful
                            if (isset($subaccount['status']) && $subaccount['status']) {
                                // Update the agent's subaccount code
                                $record->update(['subaccount_code' => $subaccount['data']['subaccount_code']]);

                                // Notify the user of success
                                Notification::make()
                                    ->title('Subaccount Created')
                                    ->body('The subaccount has been successfully created.')
                                    ->success()
                                    ->send();
                            } else {
                                // Notify the user of failure
                                Notification::make()
                                    ->title('Failed to create the subaccount. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            // Log the error and notify the user

                            Log::error('Failed to create Paystack subaccount: ' . $e->getMessage());

                            Notification::make()
                                ->title('An error occurred while creating the subaccount. Please contact support.')
                                ->danger()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => empty($record->subaccount_code))
                    ->icon('heroicon-o-plus')
                    ->color('success'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}

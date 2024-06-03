<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Agent;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Helpers\PaystackHelper;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
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
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('business_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('account_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('bank_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('referral_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('subaccount_code')
                    ->maxLength(255),
                Forms\Components\TextInput::make('percentage')
                    ->numeric()
                    ->default(20.00),
                Forms\Components\TextInput::make('fixed_rate')
                    ->numeric()
                    ->default(500.00),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bank.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('referral_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subaccount_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fixed_rate')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Joined On')
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
                            // dd($subaccountData);
                            // Attempt to create a subaccount on Paystack
                            $subaccount = PaystackHelper::createSubAccount($subaccountData);
                            // Paystack::createSubAccount($subaccountData);
                            // Log the response from Paystack
                            Log::info('Paystack subaccount creation response:', $subaccount);
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
                                    ->title('Subaccount Creation Failed')
                                    ->body('Failed to create the subaccount. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            // Log the error and notify the user
                            dd('Failed to create Paystack subaccount: ' . $e->getMessage());
                            Log::error('Failed to create Paystack subaccount: ' . $e->getMessage());

                            Notification::make()
                                ->title('Subaccount Creation Failed')
                                ->body('An error occurred while creating the subaccount. Please contact support.')
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

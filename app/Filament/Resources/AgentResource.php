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
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Filament\Tables\Actions\BulkActionGroup;
use App\Filament\Resources\AgentResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\AgentResource\RelationManagers;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';  // More appropriate icon
    protected static ?string $navigationGroup = 'Agent Management';
    protected static ?int $navigationSort = 5;
    protected static ?string $recordTitleAttribute = 'business_name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Personal Information Section
            Section::make('Personal Information')
                ->description('Agent\'s personal details')
                ->schema([
                    TextInput::make('first_name')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('last_name')
                        ->required()
                        ->maxLength(50),

                    TextInput::make('email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    TextInput::make('phone')
                        ->required()
                        ->tel()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true),
                ])->columns(2),

            // Business Information Section
            Section::make('Business Information')
                ->description('Agent\'s business details')
                ->schema([
                    TextInput::make('business_name')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    Select::make('status_id')
                        ->relationship(name: 'user.status', titleAttribute: 'name')
                        ->label('Status')
                        ->default('active')
                        ->required(),

                    TextInput::make('referral_code')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn($record) => $record !== null),

                    TextInput::make('subaccount_code')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn($record) => $record !== null),
                ])->columns(2),

            // Banking Information Section
            Section::make('Banking Information')
                ->description('Agent\'s payment and banking details')
                ->schema([
                    TextInput::make('account_number')
                        ->required()
                        ->maxLength(20)
                        ->numeric(),

                    TextInput::make('account_name')
                        ->required()
                        ->maxLength(255),

                    Select::make('bank_id')
                        ->label('Bank')
                        ->options(Bank::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable(),

                    TextInput::make('percentage')
                        ->numeric()
                        ->default(20.00)
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->required(),

                    TextInput::make('fixed_rate')
                        ->numeric()
                        ->default(500.00)
                        ->minValue(0)
                        ->prefix('₦')
                        ->required(),
                ])->columns(2),

            // Authentication Section (Only shown during creation)
            Section::make('Authentication')
                ->description('Set up login credentials')
                ->schema([
                    TextInput::make('password')
                        ->label('Password')
                        ->password()
                        ->rule(Password::default())
                        ->dehydrateStateUsing(fn($state) => Hash::make($state))
                        ->same('passwordConfirmation')
                        ->required(fn(string $operation): bool => $operation === 'create'),

                    TextInput::make('passwordConfirmation')
                        ->label('Confirm Password')
                        ->password()
                        ->dehydrated(false)
                        ->required(fn(string $operation): bool => $operation === 'create'),
                ])
                ->visible(fn(string $operation): bool => $operation === 'create')
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.full_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('business_name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.email')
                    ->label('Email')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('bank.name')
                    ->sortable(),

                TextColumn::make('percentage')
                    ->numeric(2)
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('user.status.name')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'warning',
                        'blocked' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Joined On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->relationship('user.status', 'name'),

                SelectFilter::make('bank')
                    ->relationship('bank', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-m-pencil-square'),

                Action::make('createSubaccount')
                    ->label('Create Subaccount')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->action(function ($record) {
                        try {
                            $subaccountData = [
                                'business_name' => $record->business_name,
                                'settlement_bank' => $record->bank->code,
                                'account_number' => $record->account_number,
                                'percentage_charge' => $record->percentage,
                                'primary_contact_email' => $record->user->email,
                            ];

                            $subaccount = PaystackHelper::createSubAccount($subaccountData);

                            if (isset($subaccount['status']) && $subaccount['status']) {
                                $record->update(['subaccount_code' => $subaccount['data']['subaccount_code']]);

                                Notification::make()
                                    ->success()
                                    ->title('Subaccount Created Successfully')
                                    ->body('The subaccount has been created and linked to the agent.')
                                    ->send();
                            } else {
                                throw new \Exception('Failed to create subaccount on Paystack');
                            }
                        } catch (\Exception $e) {
                            Log::error('Paystack Subaccount Creation Error', [
                                'agent_id' => $record->id,
                                'error' => $e->getMessage()
                            ]);

                            Notification::make()
                                ->danger()
                                ->title('Subaccount Creation Failed')
                                ->body('There was an error creating the subaccount. Please try again or contact support.')
                                ->persistent()
                                ->send();
                        }
                    })
                    ->visible(fn($record) => empty($record->subaccount_code))
                    ->requiresConfirmation(),

                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-m-trash'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // Add global search configuration
    public static function getGloballySearchableAttributes(): array
    {
        return ['business_name', 'user.first_name', 'user.last_name', 'user.email', 'phone'];
    }

    // Customize global search result display
    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Business' => $record->business_name,
            'Email' => $record->user->email,
            'Phone' => $record->phone,
        ];
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

<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Plan;
use Filament\Tables;
use App\Models\School;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use App\Models\Subscription;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\SchoolResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\SchoolResource\RelationManagers;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('logo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('School Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')->label('Registered On')
                    ->dateTime()
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('subscribe')
                    ->form([
                        Select::make('school_id')
                            ->label('School')
                            ->options(School::pluck('name', 'id'))
                            ->default(function ($record) { // Assuming $record is accessible and contains the school ID
                                $school = School::find($record->id);
                                return $school->id; // Directly use the school ID from the resource record
                            })
                            ->required(),

                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::all()->mapWithKeys(function ($plan) {
                                return [$plan->id => $plan->name . ' - ' . formatNaira($plan->price)]; // Now $plan is the Plan object
                            })->toArray())
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        DB::beginTransaction();

                        try {
                            $subscription = Subscription::create([
                                'school_id' => $data['school_id'],
                                'plan_id' => $data['plan_id'],
                                'status' => 'active',
                                'starts_at' => now(),
                                'ends_at' => now()->addDays(30), // Assuming a 30-day subscription
                                'subscription_code' => null, // Generate a unique subscription code
                                'next_payment_date' => now()->addDays(30), // Assuming a 30-day subscription
                            ]);

                            $planPrice = Plan::find($data['plan_id'])->price; // Assuming Plan has a price field

                            SubsPayment::create([
                                'school_id' => $data['school_id'],
                                'plan_id' => $data['plan_id'],
                                'amount' => $planPrice,
                                'net_amount' => $planPrice, // Assuming no deductions for simplicity
                                'status' => 'paid',
                                'payment_date' => now(),
                                'reference' => uniqid(), // Generate a unique payment reference
                                'subscription_id' => $subscription->id,
                            ]);

                            DB::commit();

                            // Add notification to confirm success
                            Notification::make()
                                ->title('Subscription and payment successfully created for the selected school.')
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            // Add notification for failure

                            Notification::make()
                                ->title("Failed to create subscription and payment")
                                ->danger()
                                ->send();
                            \Illuminate\Support\Facades\Log::error("Failed to create subscription and payment: {$e->getMessage()}");
                        }
                    })
                    ->visible(function ($record) {
                        return empty($record->currentSubscription());
                    })
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
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
        ];
    }
}

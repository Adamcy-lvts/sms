<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Plan;
use App\Models\User;
use Filament\Tables;
use App\Models\Agent;
use App\Models\School;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\SubsPayment;
use Illuminate\Support\Str;
use App\Models\Subscription;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Validation\Rules\Unique;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Filament\Forms\Components\ColorPicker;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\SchoolResource\Pages;
use App\Filament\Resources\SchoolResource\RelationManagers\SubscriptionsRelationManager;

class SchoolResource extends Resource
{
    protected static ?string $model = School::class;
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'School Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Group::make()
                ->schema([
                    // School Information Section
                    Section::make('School Information')
                        ->description('Basic school details')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->label('School Name')
                                ->placeholder('Enter school name'),

                            TextInput::make('name_arabic')
                                ->label('School Name (Arabic)')
                                ->placeholder('Enter school name in Arabic if applicable'),

                            TextInput::make('slug')
                                ->disabled(),

                            TextInput::make('email')
                                ->email()
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->tel()
                                ->required()
                                ->maxLength(20)
                                ->unique(ignoreRecord: true),

                            Textarea::make('address')
                                ->required()
                                ->rows(3)
                                ->columnSpanFull(),

                            FileUpload::make('logo')
                                ->image()
                                ->directory('school-logos')
                                ->imageResizeMode('cover')
                                ->imageCropAspectRatio('16:9')
                                ->imageResizeTargetWidth('1920')
                                ->imageResizeTargetHeight('1080')
                                ->columnSpanFull(),
                        ])->columns(2),

                    // School Administrator Section
                    Section::make('School Administrator')
                        ->description('Administrator account details')
                        ->schema([
                            TextInput::make('first_name')
                                ->required()
                                ->maxLength(50),

                            TextInput::make('last_name')
                                ->required()
                                ->maxLength(50),

                            TextInput::make('admin_email')
                                ->label('Admin Email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(
                                    table: 'users',
                                    column: 'email',
                                    ignorable: fn($record) => $record?->members()->first()
                                ),

                            TextInput::make('password')
                                ->password()
                                ->dehydrateStateUsing(fn($state) => Hash::make($state))
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->minLength(8)
                                ->rule(Password::default())
                                ->same('passwordConfirmation'),

                            TextInput::make('passwordConfirmation')
                                ->password()
                                ->label('Confirm Password')
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->minLength(8)
                                ->dehydrated(false),
                        ])->columns(2),
                ])
                ->columnSpan(['lg' => 2]),

            // Settings & Configuration Sidebar
            Group::make()
                ->schema([
                    Section::make('School Settings')
                        ->schema([
                            ColorPicker::make('theme_color')
                                ->label('Theme Color'),

                            Toggle::make('is_active')
                                ->label('Active Status')
                                ->default(true),

                            Select::make('agent_id')
                                ->options(Agent::all()->pluck('business_name', 'id'))
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('business_name')
                                        ->required(),
                                    TextInput::make('email')
                                        ->email()
                                        ->required(),
                                    TextInput::make('phone')
                                        ->tel()
                                        ->required(),
                                ]),

                            Select::make('timezone')
                                ->options(self::getTimezoneOptions())
                                ->searchable()
                                ->default('Africa/Lagos'),

                            Toggle::make('allow_registration')
                                ->label('Allow Student Registration')
                                ->default(true)
                                ->helperText('Enable/disable new student registration'),

                            DatePicker::make('academic_year_start')
                                ->label('Academic Year Start')
                                ->default(now()->startOfYear()),
                        ]),

                    Section::make('Subscription Information')
                        ->schema([
                            Select::make('default_plan_id')
                                ->options(Plan::all()->pluck('name', 'id'))
                                ->label('Current Plan')
                                ->disabled()
                                ->visible(fn($record) => $record?->currentSubscription()),

                            TextInput::make('subscription_status')
                                ->disabled()
                                ->visible(fn($record) => $record?->currentSubscription())
                                ->default('No active subscription'),
                        ]),
                ])
                ->columnSpan(['lg' => 1]),
        ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-school.png')),

                TextColumn::make('name')
                    ->label('School Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->copyable()
                    ->searchable(),

                TextColumn::make('currentSubscription')
                    ->label('Current Plan')
                    ->getStateUsing(function ($record) {
                        try {
                            $subscription = $record->currentSubscription();
                            return $subscription?->plan?->name ?? 'No Active Plan';
                        } catch (\Exception $e) {
                            return 'No Active Plan';
                        }
                    })
                    ->badge()
                    ->color(function ($record) {
                        try {
                            return $record->currentSubscription()?->plan ? 'success' : 'danger';
                        } catch (\Exception $e) {
                            return 'danger';
                        }
                    }),

                TextColumn::make('agent.business_name')
                    ->label('Agent')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Registered On')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                // SelectFilter::make('subscription_status')
                //     ->options([
                //         'active' => 'Active Subscription',
                //         'expired' => 'Expired Subscription',
                //         'none' => 'No Subscription',
                //     ])
                //     ->query(function (Builder $query, array $data) {
                //         if (empty($data['value'])) return $query;

                //         return match ($data['value']) {
                //             'active' => $query->whereHas('subscriptions', fn($q) =>
                //             $q->where('status', 'active')
                //                 ->where('ends_at', '>', now())),
                //             'expired' => $query->whereHas('subscriptions', fn($q) =>
                //             $q->where('ends_at', '<', now())),
                //             'none' => $query->whereDoesntHave('subscriptions'),
                //         };
                //     }),

                // SelectFilter::make('agent')
                //     ->options(Agent::all()->pluck('business_name', 'id'))
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('manage_subscription')
                    ->label(fn($record) => $record->currentSubscription()
                        ? 'Change Plan'
                        : 'Add Subscription')
                    ->icon('heroicon-o-credit-card')
                    ->form([
                        Select::make('plan_id')
                            ->label('Plan')
                            ->options(Plan::all()->mapWithKeys(function ($plan) {
                                return [$plan->id => "{$plan->name} - " . formatNaira($plan->price)];
                            }))
                            ->required(),

                        Toggle::make('apply_trial')
                            ->label('Apply Trial Period')
                            ->default(false)
                        // ->visible(fn($record) => !$record->hasHadTrial()),
                    ])
                    ->action(function (School $record, array $data): void {
                        DB::transaction(function () use ($record, $data) {
                            try {
                                $plan = Plan::findOrFail($data['plan_id']);

                                // Handle existing subscription
                                if ($current = $record->currentSubscription()) {
                                    $current->update(['status' => 'cancelled']);
                                }

                                // Create new subscription
                                $subscription = Subscription::create([
                                    'school_id' => $record->id,
                                    'plan_id' => $plan->id,
                                    'status' => 'active',
                                    'starts_at' => now(),
                                    'ends_at' => now()->addDays($plan->duration),
                                    'is_trial' => $data['apply_trial'] ?? false,
                                    'trial_ends_at' => $data['apply_trial']
                                        ? now()->addDays($plan->trial_period)
                                        : null,
                                ]);

                                // Create payment record
                                if (!$data['apply_trial']) {
                                    SubsPayment::create([
                                        'school_id' => $record->id,
                                        'amount' => $plan->price,
                                        'status' => 'paid',
                                        'payment_date' => now(),
                                        'subscription_id' => $subscription->id,
                                    ]);
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Subscription Updated')
                                    ->body('The subscription has been successfully updated.')
                                    ->send();
                            } catch (\Exception $e) {
                                throw $e;
                            }
                        });
                    }),

                Tables\Actions\DeleteAction::make()
                    ->before(function (School $record) {
                        if ($record->currentSubscription()) {
                            Notification::make()
                                ->danger()
                                ->title('Cannot delete school with active subscription')
                                ->send();

                            $this->halt();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            if ($records->contains(fn($record) => $record->currentSubscription())) {
                                Notification::make()
                                    ->danger()
                                    ->title('Some schools have active subscriptions')
                                    ->body('Schools with active subscriptions cannot be deleted.')
                                    ->send();

                                $this->halt();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            SubscriptionsRelationManager::class,
            // RelationManagers\StudentsRelationManager::class,
            // RelationManagers\StaffRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSchools::route('/'),
            'create' => Pages\CreateSchool::route('/create'),
            'edit' => Pages\EditSchool::route('/{record}/edit'),
            // 'view' => Pages\ViewSchool::route('/{record}'),
        ];
    }

    protected static function getTimezoneOptions(): array
    {
        return [
            'Africa/Lagos' => 'Lagos (GMT+1)',
            'Africa/Cairo' => 'Cairo (GMT+2)',
            'Africa/Nairobi' => 'Nairobi (GMT+3)',
            // Add more as needed
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'phone', 'address'];
    }
}

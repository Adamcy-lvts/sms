<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Plan;
use Filament\Tables;
use App\Models\Feature;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\RichEditor;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ToggleButtons;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\PlanResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PlanResource\RelationManagers;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Subscription Management';
    protected static ?int $navigationSort = 4;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Plan Information
                Section::make('Basic Information')
                    ->description('Core plan details and pricing')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->label('Plan Name')
                            ->maxLength(100)
                            ->placeholder('e.g., Basic, Standard, Premium'),

                        Select::make('interval')
                            ->options([
                                'monthly' => 'Monthly',
                                'annually' => 'Yearly',
                            ])
                            ->required()
                            ->default('monthly')
                            ->reactive(),

                        TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('₦')
                            ->minValue(0)
                            ->step(100)
                            ->placeholder('0.00')
                            ->helperText(
                                fn(callable $get) =>
                                $get('interval') === 'annually' ?
                                    'Enter the full annual price' :
                                    'Enter the monthly price'
                            ),

                        TextInput::make('yearly_discount')
                            ->numeric()
                            ->label('Yearly Discount (%)')
                            ->minValue(0)
                            ->maxValue(100)
                            ->visible(fn(callable $get) => $get('interval') === 'annually'),

                        TextInput::make('duration')
                            ->required()
                            ->numeric()
                            ->label('Duration (in days)')
                            ->default(fn(callable $get) => $get('interval') === 'annually' ? 365 : 30)
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Automatically set based on interval'),

                        TextInput::make('trial_period')
                            ->numeric()
                            ->label('Trial Period (in days)')
                            ->default(0)
                            ->minValue(0)
                            ->suffix('days')
                            ->helperText('Set to 0 for no trial period'),

                        Checkbox::make('has_trial')
                            ->label('Enable Trial Period')
                            ->default(false)
                            ->reactive(),
                    ])->columns(2),

                // Plan Features Section
                Section::make('Plan Features')
                    ->description('Define what\'s included in this plan')
                    ->schema([
                        CheckboxList::make('features')
                            ->options(
                                Feature::all()
                                    ->mapWithKeys(fn($feature) => [$feature->id => $feature->name])
                                    ->toArray()
                            )
                            ->searchable()
                            ->columns(3)
                            ->columnSpanFull()
                            ->reactive(),

                        Group::make()
                            ->schema([
                                TextInput::make('featureLimits.max_students')
                                    ->label('Maximum Students')
                                    ->numeric()
                                    ->placeholder('Leave empty for unlimited')
                                    ->visible(fn($get) => collect($get('features'))->contains(
                                        fn($id) => Feature::find($id)?->slug === 'students_limit'
                                    )),

                                TextInput::make('featureLimits.max_staff')
                                    ->label('Maximum Staff')
                                    ->numeric()
                                    ->placeholder('Leave empty for unlimited')
                                    ->visible(fn($get) => collect($get('features'))->contains(
                                        fn($id) => Feature::find($id)?->slug === 'staff_limit'
                                    )),

                                TextInput::make('featureLimits.max_classes')
                                    ->label('Maximum Classes')
                                    ->numeric()
                                    ->placeholder('Leave empty for unlimited')
                                    ->visible(fn($get) => collect($get('features'))->contains(
                                        fn($id) => Feature::find($id)?->slug === 'classes_limit'
                                    )),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),

                        RichEditor::make('description')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        TextInput::make('cto')
                            ->label('Call to Action Text')
                            ->placeholder('e.g., Start with Basic, Upgrade to Premium')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // Plan Limits & Settings
                Section::make('Plan Limits & Settings')
                    ->description('Configure usage limits and display settings')
                    ->schema([
                        TextInput::make('plan_code')
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn($record) => $record !== null)
                            ->helperText('Automatically generated by Paystack'),

                        ToggleButtons::make('status')
                            ->inline()
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'archived' => 'Archived',
                            ])
                            ->default('active')
                            ->colors([
                                'active' => 'success',
                                'inactive' => 'danger',
                                'archived' => 'warning',
                            ]),

                        ColorPicker::make('badge_color')
                            ->label('Plan Badge Color'),

                        TextInput::make('max_students')
                            ->numeric()
                            ->label('Maximum Students')
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of students allowed'),

                        TextInput::make('max_staff')
                            ->numeric()
                            ->label('Maximum Staff')
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of staff allowed'),

                        TextInput::make('max_classes')
                            ->numeric()
                            ->label('Maximum Classes')
                            ->placeholder('Leave empty for unlimited')
                            ->helperText('Maximum number of classes allowed'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price')
                    ->money('NGN')
                    ->sortable(),

                TextColumn::make('interval')
                    ->badge()
                    ->colors([
                        'primary' => 'monthly',
                        'warning' => 'quarterly',
                        'success' => 'yearly',
                    ]),

                TextColumn::make('duration')
                    ->suffix(' days')
                    ->sortable(),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'archive',
                    ]),

                TextColumn::make('active_subscriptions_count')
                    ->label('Active Subscriptions')
                    ->counts('activeSubscriptions')
                    ->sortable(),

                TextColumn::make('trial_period')
                    ->suffix(' days')
                    ->default('No trial'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'archive' => 'Archived',
                    ]),

                SelectFilter::make('interval')
                    ->options([
                        'monthly' => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'yearly' => 'Yearly',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->activeSubscriptions()->count() === 0),

                Tables\Actions\Action::make('manage_features')
                    ->icon('heroicon-o-adjustments-horizontal')
                    // ->url(fn($record) => route('admin.plans.features', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function (Collection $records) {
                            $records->each(function ($record) {
                                if ($record->activeSubscriptions()->count() === 0) {
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}

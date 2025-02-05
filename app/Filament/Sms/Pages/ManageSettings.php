<?php

namespace App\Filament\Sms\Pages;

use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Pages\Page;
use App\Models\PaymentType;
use App\Models\SchoolSettings;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use App\Helpers\EmployeeIdFormats;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use App\Services\EmployeeIdGenerator;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Section;
use App\Helpers\AdmissionNumberFormats;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Services\AdmissionNumberGenerator;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageSettings extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'School Settings';
    protected static ?string $navigationGroup = 'Settings';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'settings';
    protected static string $view = 'filament.sms.pages.manage-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $tenant = Filament::getTenant();

        $settings = SchoolSettings::getSettings($tenant->id);
        // // This will automatically create settings if they don't exist
        // $settings = $tenant->getSettingsAttribute();

        $this->form->fill([
            // Employee ID Settings

            'employee_settings' => [

                'prefix' => $settings->employee_settings['prefix'] ?? 'EMP',
                'number_length' => $settings->employee_settings['number_length'] ?? 3,
                'separator' => $settings->employee_settings['separator'] ?? '-',
                'include_prefix' => $settings->employee_settings['include_prefix'] ?? true,
                'include_year' => $settings->employee_settings['include_year'] ?? true,
                'include_separator' => $settings->employee_settings['include_separator'] ?? true,
                'prefix_type' => $settings->employee_settings['prefix_type'] ?? 'default',
                'year_format' => $settings->employee_settings['year_format'] ?? 'short',
                'custom_year' => $settings->employee_settings['custom_year'] ?? null,

            ],

            'academic_settings' => [
                'auto_set_period' => $settings->academic_settings['auto_set_period'] ?? true,
                'allow_overlap' => $settings->academic_settings['allow_overlap'] ?? false,
                'year_start_month' => $settings->academic_settings['year_start_month'] ?? 9,
                'year_end_month' => $settings->academic_settings['year_end_month'] ?? 7,
                'term_duration' => $settings->academic_settings['term_duration'] ?? 3,
            ],

            // Admission Number Settings
            'admission_settings' => [
                'format_type' => $settings->admission_settings['format_type'] ?? 'basic',
                'custom_format' => $settings->admission_settings['custom_format'] ?? null,
                'school_prefix' => $settings->admission_settings['school_prefix'] ?? null,
                'length' => $settings->admission_settings['length'] ?? 4,
                'separator' => $settings->admission_settings['separator'] ?? '-',
                'initials_method' => $settings->admission_settings['initials_method'] ?? 'first_letters',
                'session_format' => $settings->admission_settings['session_format'] ?? 'short',
                'number_start' => $settings->admission_settings['number_start'] ?? 1,
                'reset_sequence_by_session' => $settings->admission_settings['reset_sequence_by_session'] ?? true,
                'include_session' => $settings->admission_settings['include_session'] ?? true,
                'custom_session' => $settings->admission_settings['custom_session'] ?? null,
                'include_separator' => $settings->admission_settings['include_separator'] ?? true,
                'include_prefix' => $settings->admission_settings['include_prefix'] ?? true,
                'manual_numbering' => $settings->admission_settings['manual_numbering'] ?? false,
                'show_last_number' => $settings->admission_settings['show_last_number'] ?? true,
            ],

            // Add payment settings to the form fill
            'payment_settings' => [
                'default_payment_type' => $settings->payment_settings['default_payment_type'] ?? null,
                'allow_session_payment' => $settings->payment_settings['allow_session_payment'] ?? true,
                'due_dates' => [
                    'default_days' => $settings->payment_settings['due_dates']['default_days'] ?? 7,
                    'term_payment_types' => $settings->payment_settings['due_dates']['term_payment_types'] ?? [],
                ],
            ],
        ]);
    }


    public function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Settings')
                ->tabs([
                    Tabs\Tab::make('Admission Number Settings')
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            // Add these at the top of your admission settings schema
                            Forms\Components\Toggle::make('admission_settings.manual_numbering')
                                ->label('Manual Admission Number Entry')
                                ->live()
                                ->default(false)
                                ->helperText('Allow manual input of admission numbers instead of automatic generation'),

                            Forms\Components\Toggle::make('admission_settings.show_last_number')
                                ->label('Show Last Used Number')
                                ->default(true)
                                ->visible(fn (Get $get) => $get('admission_settings.manual_numbering'))
                                ->helperText('Display the last used admission number when entering new numbers'),

                            // Forms\Components\Select::make('admission_number_format_type')
                            
                            Forms\Components\Select::make('admission_settings.initials_method')
                                ->label('Prefix Generation Method')
                                ->helperText('Select how to generate prefix from school name')
                                ->options([
                                    'first_letters' => 'First Letters (e.g., KPS)',
                                    'consonants' => 'First Consonants (e.g., KHL)',
                                ])
                                ->required()
                                ->live()
                                ->default('first_letters')
                                ->visible(fn(Get $get) => $get('admission_settings.include_prefix'))
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $schoolName = Filament::getTenant()->name;
                                    $generator = new AdmissionNumberGenerator();
                                    $useConsonants = $state === 'consonants';
                                    $prefix = $generator->generateSchoolInitials($useConsonants);
                                    $set('admission_settings.school_prefix', $prefix);
                                }),

                            Forms\Components\TextInput::make('admission_settings.school_prefix')
                                ->label('School Prefix')
                                ->maxLength(3)
                                ->required()
                                ->live()
                                ->disabled()
                                ->dehydrated()
                                ->visible(fn(Get $get) => $get('admission_settings.include_prefix'))
                                ->helperText('Generated from school name based on selected method'),

                            Forms\Components\TextInput::make('session_custom_format')
                                ->label('Custom Session Format')
                                ->visible(fn(Get $get) => $get('session_format') === 'custom')
                                ->placeholder('YYYY/YYYY+1'),

                            Forms\Components\TextInput::make('admission_settings.length')
                                ->label('Sequential Number Length')
                                ->numeric()
                                ->live()
                                ->default(4)
                                ->required()
                                ->minValue(1)
                                ->maxValue(6),

                            Forms\Components\Toggle::make('admission_settings.include_session')
                                ->label('Include Session in Number')
                                ->default(true)
                                ->live(),

                            Forms\Components\Select::make('admission_settings.custom_session')
                                ->label('Use Specific Session')
                                ->options(fn() => AcademicSession::orderByDesc('created_at')->pluck('name', 'name'))
                                ->searchable()
                                ->preload()
                                ->live()
                                ->helperText('Leave empty to use current session')
                                ->visible(fn(Get $get) => $get('admission_settings.include_session')),

                            Forms\Components\Select::make('admission_settings.session_format')
                                ->label('Session Format')
                                ->options([
                                    'short' => 'Short Year (23)',
                                    'short_session' => 'Short Session (2324)',
                                    'full_year' => 'Full Year (2023)',
                                    'full_session' => 'Full Session (2023/2024)',
                                ])
                                ->visible(fn(Get $get) => $get('admission_settings.include_session'))
                                ->live()
                                ->required(fn(Get $get) => $get('admission_settings.include_session')),

                            Forms\Components\Toggle::make('admission_settings.reset_sequence_by_session')
                                ->label('Reset Sequence for Each Session')
                                ->default(true)
                                ->helperText('Start numbering from 1 for each new session')
                                ->visible(fn(Get $get) => $get('admission_settings.include_session')),

                            Forms\Components\Toggle::make('admission_settings.include_separator')
                                ->label('Use Separator')
                                ->default(true)
                                ->live()
                                ->helperText('Add separator between parts of the admission number'),

                            Forms\Components\TextInput::make('admission_settings.separator')
                                ->label('Separator Character')
                                ->maxLength(1)
                                ->live()
                                ->default('-')
                                ->visible(fn(Get $get) => $get('admission_settings.include_separator')),

                            Forms\Components\Toggle::make('admission_settings.include_prefix')
                                ->label('Include School Prefix')
                                ->default(true)
                                ->live()
                                ->helperText('Include school prefix in the admission number'),



                            Forms\Components\Placeholder::make('admission_preview')
                                ->label('Number Format Preview')
                                ->content(function (Get $get) {
                                    $generator = new AdmissionNumberGenerator();
                                    return $generator->previewFormat([
                                        'format_type' => $get('admission_settings.format_type'),
                                        'custom_format' => $get('admission_settings.custom_format'),
                                        'school_prefix' => $get('admission_settings.school_prefix'),
                                        'separator' => $get('admission_settings.separator'),
                                        'length' => $get('admission_settings.length'),
                                        'include_session' => $get('admission_settings.include_session'),
                                        'custom_session' => $get('admission_settings.custom_session'),
                                        'session_format' => $get('admission_settings.session_format'),
                                        'initials_method' => $get('admission_settings.initials_method'),
                                        'include_separator' => $get('admission_settings.include_separator'),
                                        'include_prefix' => $get('admission_settings.include_prefix'),
                                    ]);
                                })
                                ->live(),



                        ])->columns(2),
                    Tabs\Tab::make('Employee ID Settings')
                        ->schema([

                            Forms\Components\Select::make('employee_settings.prefix_type')
                                ->label('School Name Format')
                                ->helperText('Select how to generate school initials, consonant means first consonants of words, first letters means first letter of each word of the school name')
                                ->options([
                                    'consonants' => 'Consonants (KHL)',
                                    'first_letters' => 'First Letters (KIA)',
                                ])
                                ->default('consonants')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $schoolName = Filament::getTenant()->name;
                                    $generator = new EmployeeIdGenerator();
                                    $initials = $generator->generateSchoolInitials($state === 'consonants');
                                    $set('employee_settings.prefix', $initials);
                                }),


                            Forms\Components\TextInput::make('employee_settings.prefix')
                                ->label('Default Prefix')
                                ->default('EMP')
                                ->live()
                                ->required(),

                            Forms\Components\Select::make('employee_settings.year_format')
                                ->label('Year Format')
                                ->options([
                                    'short' => 'Short Year (23)',
                                    'full' => 'Full Year (2023)'
                                ])
                                ->default('short')
                                ->live()
                                ->visible(fn(Get $get) => $get('employee_settings.include_year')),

                            Forms\Components\DatePicker::make('employee_settings.custom_year')
                                ->label('Start Year')
                                ->format('Y')
                                ->maxDate(now())
                                ->helperText('Leave empty to use current year')
                                ->live(),

                            Forms\Components\TextInput::make('employee_settings.number_length')
                                ->label('Sequential Number Length')
                                ->numeric()
                                ->live()
                                ->default(3)
                                ->required()
                                ->minValue(1)
                                ->maxValue(6),

                            Forms\Components\KeyValue::make('employee_settings.department_prefixes')
                                ->label('Department Prefixes')
                                ->keyLabel('Department')
                                ->valueLabel('Prefix')
                                ->reorderable()
                                ->visible(fn(Get $get) => $get('employee_settings.format_type') === 'department'),

                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Toggle::make('employee_settings.include_prefix')
                                        ->label('Include Prefix')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Include school prefix in ID format'),

                                    Forms\Components\Toggle::make('employee_settings.include_year')
                                        ->label('Include Year')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Include year in ID format'),

                                    Forms\Components\Toggle::make('employee_settings.include_separator')
                                        ->label('Use Separator')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Add separator between parts'),

                                    Forms\Components\TextInput::make('employee_settings.separator')
                                        ->label('Separator Character')
                                        ->default('/')
                                        ->maxLength(1)
                                        ->visible(fn(Get $get) => $get('employee_settings.include_separator')),
                                ]),

                            Forms\Components\Placeholder::make('preview')
                                ->label('Format Preview')
                                ->content(function (Get $get) {
                                    $generator = new EmployeeIdGenerator();
                                    return $generator->previewFormat([
                                        'include_prefix' => $get('employee_settings.include_prefix'),
                                        'include_year' => $get('employee_settings.include_year'),
                                        'include_separator' => $get('employee_settings.include_separator'),
                                        'separator' => $get('employee_settings.separator'),
                                        'initials_method' => $get('employee_settings.initials_method'),
                                        'prefix' => $get('employee_settings.prefix'),
                                        'prefix_type' => $get('employee_settings.prefix_type'),
                                        'year_format' => $get('employee_settings.year_format'),
                                        'number_length' => $get('employee_settings.number_length'),
                                        'custom_year' => $get('employee_settings.custom_year'),
                                    ]);
                                }),
                        ])
                        ->columns(2),

                    Tabs\Tab::make('Academic Settings')
                        ->schema([
                            Forms\Components\Toggle::make('academic_settings.auto_set_period')
                                ->label('Auto-set Current Period')
                                ->helperText('Automatically set current academic period based on dates')
                                ->default(true),

                            Forms\Components\Toggle::make('academic_settings.allow_overlap')
                                ->label('Allow Term Overlap')
                                ->helperText('Allow terms to have overlapping dates')
                                ->default(false),

                            Forms\Components\Select::make('academic_settings.year_start_month')
                                ->label('Academic Year Start Month')
                                ->options([
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December'
                                ])
                                ->default(9)
                                ->required(),

                            Forms\Components\Select::make('academic_settings.year_end_month')
                                ->label('Academic Year End Month')
                                ->options([
                                    1 => 'January',
                                    2 => 'February',
                                    3 => 'March',
                                    4 => 'April',
                                    5 => 'May',
                                    6 => 'June',
                                    7 => 'July',
                                    8 => 'August',
                                    9 => 'September',
                                    10 => 'October',
                                    11 => 'November',
                                    12 => 'December'
                                ])
                                ->default(7)
                                ->required(),

                            Forms\Components\TextInput::make('academic_settings.term_duration')
                                ->label('Default Term Duration (months)')
                                ->numeric()
                                ->default(3)
                                ->required()
                                ->minValue(1)
                                ->maxValue(6),
                        ])
                        ->columns(2),
                    Tabs\Tab::make('Payment Settings')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Section::make([
                                Grid::make(2)
                                    ->schema([

                                        TextInput::make('payment_settings.due_dates.default_days')
                                            ->label('Default Due Days')
                                            ->numeric()
                                            ->default(7)
                                            ->helperText('Default number of days after term start for payments'),
                                    ]),

                                Repeater::make('payment_settings.due_dates.term_payment_types')
                                    ->label('Term Payment Due Dates')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('payment_type')
                                                    ->label('Payment Type')
                                                    ->options(function () {
                                                        return PaymentType::where('school_id', Filament::getTenant()->id)
                                                            ->where('active', true)
                                                            ->pluck('name', 'id');
                                                    })
                                                    ->required(),

                                                TextInput::make('days')
                                                    ->label('Days After Term Start')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->required(),
                                            ]),
                                    ])
                                    ->columnSpanFull()
                                    ->collapsible()
                                    ->defaultItems(0),

                                Toggle::make('payment_settings.allow_session_payment')
                                    ->label('Allow Session Payments')
                                    ->helperText('Allow parents to make payments for entire session')
                                    ->default(true),
                            ])
                                ->columns(1),
                        ]),
                ])->persistTabInQueryString('manage-settings')
                ->columnSpanFull()
        ])->statePath('data');
    }


    public function save(): void
    {
        $data = $this->form->getState();

        $tenant = Filament::getTenant();
        $settings = $tenant->settings;

        $settings->update([
            'admission_settings' => $data['admission_settings'],
            'employee_settings' => $data['employee_settings'] ?? [],
            'academic_settings' => $data['academic_settings'] ?? [],
            // Add payment settings to the update
            'payment_settings' => $data['payment_settings'] ?? [
                'default_payment_type' => null,
                'allow_session_payment' => true,
                'due_dates' => [
                    'default_days' => 7,
                    'term_payment_types' => [],
                ],
            ],
        ]);

        // Clear all cached settings for this school
        Cache::tags(["school:{$tenant->slug}"])->flush();

        Notification::make()
            ->title('Settings saved successfully')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Forms\Components\Actions\Action::make('save')
                ->label('Save Settings')
                ->submit('save'),
        ];
    }
}

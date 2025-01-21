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
use App\Helpers\EmployeeIdFormats;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
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
                'format_type' => $settings->employee_settings['format_type'] ?? 'basic',
                'custom_format' => $settings->employee_settings['custom_format'] ?? null,
                'prefix' => $settings->employee_settings['prefix'] ?? 'EMP',
                'number_length' => $settings->employee_settings['number_length'] ?? 3,
                'separator' => $settings->employee_settings['separator'] ?? '-',
                'department_prefixes' => $settings->employee_settings['department_prefixes'] ?? [],
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
                'prefix' => $settings->admission_settings['prefix'] ?? 'ADM',
                'length' => $settings->admission_settings['length'] ?? 4,
                'separator' => $settings->admission_settings['separator'] ?? '-',
                'school_initials' => $settings->admission_settings['school_initials'] ?? null,
                'initials_method' => $settings->admission_settings['initials_method'] ?? 'first_letters',
                'session_format' => $settings->admission_settings['session_format'] ?? 'short',
                'number_start' => $settings->admission_settings['number_start'] ?? 1,
                'reset_sequence_yearly' => $settings->admission_settings['reset_sequence_yearly'] ?? false,
                'reset_sequence_by_session' => $settings->admission_settings['reset_sequence_by_session'] ?? false,
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
                            // Forms\Components\Select::make('admission_number_format_type')
                            Forms\Components\Select::make('admission_settings.format_type')
                                ->label('Number Format Type')
                                ->options([
                                    'basic' => 'Basic (ADM-0001)',
                                    'with_year' => 'With Year (ADM-23-001)',
                                    'school_initials' => 'School Initials (KPS-001)',
                                    'school_year' => 'School with Year (KPS-23-001)',
                                    'with_session' => 'With Session and default prefix (ADM-2324-001)',
                                    'school_session' => 'School with Session (KPS-2324-001)',
                                    'custom' => 'Custom Format'
                                ])
                                ->required()
                                ->live()
                                ->default('basic')
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($state === 'custom') {
                                        $savedFormat = $get('admission_settings.custom_format');
                                        if ($savedFormat) {
                                            $set('admission_settings.custom_format', $savedFormat);
                                        }
                                    }
                                }),

                            Forms\Components\TextInput::make('admission_settings.custom_format')
                                ->label('Custom Format Template')
                                ->placeholder('KIA/{YYYY}/{NUM}')
                                ->helperText(new HtmlString('Available tokens: ' . implode(', ', array_keys(AdmissionNumberFormats::getAvailableTokens()))))
                                ->visible(fn(Get $get) => $get('admission_settings.format_type') === 'custom')
                                ->required(fn(Get $get) => $get('admission_settings.format_type') === 'custom')
                                ->live()
                                ->debounce(500)
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    $generator = new AdmissionNumberGenerator();
                                    $preview = $generator->previewFormat($state);
                                    $set('admission_settings.preview', $preview);
                                }),

                            Forms\Components\Placeholder::make('admission_settings.preview')
                                ->label('Format Preview')
                                ->content(fn(Get $get) => $get('admission_settings.preview') ?? 'Enter a valid format to see preview')
                                ->visible(fn(Get $get) => $get('admission_settings.format_type') === 'custom'),

                            // Hidden field to store custom format
                            Forms\Components\Hidden::make('saved_custom_format'),


                            Forms\Components\TextInput::make('admission_settings.prefix')
                                ->label('Default Prefix')
                                ->default('ADM')
                                // ->placeholder('ADM')
                                ->maxLength(5),



                            Forms\Components\TextInput::make('admission_settings.school_initials')
                                ->label('Custom School Initials')
                                ->live()
                                ->helperText('Leave empty to auto-generate from school name')
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    if (!empty($state)) {
                                        $set('admission_settings.number_prefix', $state);
                                    }
                                }),

                            Forms\Components\Select::make('admission_settings.initials_method')
                                ->label('School Initials Generation Method')
                                ->options([
                                    'first_letters' => 'First Letters (KPS)',
                                    'significant_words' => 'Skip Common Words',
                                    'consonants' => 'First Consonants'
                                ])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    // Get tenant name
                                    $schoolName = Filament::getTenant()->name;

                                    // Generate initials based on selected method
                                    $initials = match ($state) {
                                        'first_letters' => $this->getFirstLettersInitials($schoolName),
                                        'significant_words' => $this->getSignificantWordsInitials($schoolName),
                                        'consonants' => $this->getFirstConsonants($schoolName),
                                        default => $this->getFirstLettersInitials($schoolName)
                                    };

                                    // Update both school initials and default prefix if custom initials is empty
                                    if (empty($get('school_initials'))) {
                                        $set('school_initials', $initials);
                                    }
                                    $set('admission_number_prefix', $initials);
                                }),


                            Forms\Components\Select::make('admission_settings.session_format')
                                ->label('Session Format')
                                ->options([
                                    'short' => 'Short Year (23)',
                                    'short_session' => 'Short Session (2324)',
                                    'full_year' => 'Full Year (2023)',
                                    'full_session' => 'Full Session (2023/2024)',
                                    'custom' => 'Custom Format'
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('session_custom_format')
                                ->label('Custom Session Format')
                                ->visible(fn(Get $get) => $get('session_format') === 'custom')
                                ->placeholder('YYYY/YYYY+1'),

                            Forms\Components\TextInput::make('admission_settings.length')
                                ->label('Sequential Number Length')
                                ->numeric()
                                ->default(4)
                                ->required()
                                ->minValue(1)
                                ->maxValue(6),

                            Forms\Components\TextInput::make('admission_settings.separator')
                                ->label('Separator Character')
                                ->maxLength(1)
                                ->default('-'),

                        ])->columns(2),
                    Tabs\Tab::make('Employee ID Settings')
                        ->schema([
                            Forms\Components\Select::make('employee_settings.format_type')
                                ->label('ID Format Type')
                                ->options([
                                    'basic' => 'Basic (EMP001)',
                                    'with_year' => 'With Year (EMP23001)',
                                    'department' => 'With Department (ADM23001)',
                                    'custom' => 'Custom Format'
                                ])
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('employee_settings.custom_format')
                                ->label('Custom Format')
                                ->placeholder('EMP/{YY}/{NUM}')
                                ->helperText('Available tokens: {PREFIX}, {YY}, {NUM}, {DEPT}')
                                ->visible(fn(Get $get) => $get('employee_settings.format_type') === 'custom'),

                            Forms\Components\Select::make('employee_settings.prefix_type')
                                ->label('Prefix Type')
                                ->options([
                                    'default' => 'Default (EMP)',
                                    'school' => 'School Name',
                                    'custom' => 'Custom'
                                ])
                                ->default('default')
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                    if ($state === 'school') {
                                        $schoolName = Filament::getTenant()->name;
                                        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $schoolName), 0, 3));
                                        $set('employee_settings.prefix', $prefix);
                                    } elseif ($state === 'default') {
                                        $set('employee_settings.prefix', 'EMP');
                                    }
                                }),

                            Forms\Components\TextInput::make('employee_settings.prefix')
                                ->label('Default Prefix')
                                ->default('EMP')
                                ->required(),

                            Forms\Components\Select::make('employee_settings.year_format')
                                ->label('Year Format')
                                ->options([
                                    'none' => 'No Year',
                                    'short' => 'Short Year (23)',
                                    'full' => 'Full Year (2023)'
                                ])
                                ->default('short')
                                ->visible(fn(Get $get) => in_array(
                                    $get('employee_settings.format_type'),
                                    ['with_year', 'with_department']
                                )),

                            Forms\Components\TextInput::make('employee_settings.number_length')
                                ->label('Sequential Number Length')
                                ->numeric()
                                ->default(3)
                                ->required()
                                ->minValue(1)
                                ->maxValue(6),

                            Forms\Components\TextInput::make('employee_settings.separator')
                                ->label('Separator Character')
                                ->maxLength(1)
                                ->default('-'),

                            Forms\Components\KeyValue::make('employee_settings.department_prefixes')
                                ->label('Department Prefixes')
                                ->keyLabel('Department')
                                ->valueLabel('Prefix')
                                ->reorderable()
                                ->visible(fn(Get $get) => $get('employee_settings.format_type') === 'department')
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

    // Helper methods for initials generation
    protected function getFirstLettersInitials(string $name): string
    {
        $words = array_filter(explode(' ', $name));
        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 3));
        }
        $initials = array_slice(array_map(fn($word) => substr($word, 0, 1), $words), 0, 3);
        return strtoupper(implode('', $initials));
    }

    protected function getSignificantWordsInitials(string $name): string
    {
        $skipWords = ['the', 'of', 'and', 'in', 'at', 'by', 'for'];
        $words = array_filter(
            explode(' ', strtolower($name)),
            fn($word) => !in_array($word, $skipWords)
        );
        $initials = array_map(fn($word) => substr($word, 0, 1), $words);
        return strtoupper(implode('', array_slice($initials, 0, 3)));
    }

    protected function getFirstConsonants(string $name): string
    {
        $words = array_filter(explode(' ', $name));
        $consonants = '';

        foreach ($words as $word) {
            if (preg_match('/[bcdfghjklmnpqrstvwxyz]/i', $word, $matches)) {
                $consonants .= $matches[0];
            } else {
                $consonants .= substr($word, 0, 1);
            }
            if (strlen($consonants) >= 3) break;
        }

        return strtoupper(substr(str_pad($consonants, 3, 'X'), 0, 3));
    }

    public function previewFormat(string $format): string
    {
        $replacements = [
            '{PREFIX}' => 'ADM',
            '{SCHOOL}' => 'KPS',
            '{YY}' => date('y'),
            '{YYYY}' => date('Y'),
            '{SESSION}' => '2324',
            '{NUM}' => '0001',
            '{SEP}' => '-'
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
}

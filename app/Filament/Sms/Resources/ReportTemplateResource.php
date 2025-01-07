<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ReportTemplate;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Illuminate\Contracts\View\View;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use App\Services\TemplatePreviewService;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ColorPicker;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\ReportTemplateResource\Pages;
use App\Filament\Sms\Resources\ReportTemplateResource\RelationManagers;

class ReportTemplateResource extends Resource
{
    protected static ?string $model = ReportTemplate::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';
    protected static ?string $navigationGroup = 'Academic Management';
    protected static ?string $navigationLabel = 'Report Templates';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Report Template')
                ->tabs([
                    // Basic Information Tab
                    Tabs\Tab::make('Basic Information')
                        ->schema([
                            TextInput::make('name')
                                ->label('Template Name')
                                ->required()
                                ->placeholder('e.g., Standard Term Report'),

                            TextInput::make('description')
                                ->label('Description')
                                ->placeholder('e.g., Standard end of term report card template'),

                            Toggle::make('is_default')
                                ->label('Set as Default Template')
                                ->helperText('Only one template can be default'),

                            Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),
                        ]),

                    // Updated Header Config Tab    
                    Tabs\Tab::make('Header')
                        ->schema([
                            Section::make('Logo Settings')
                                ->schema([
                                    Toggle::make('header_config.show_logo')
                                        ->label('Show School Logo')
                                        ->default(true)
                                        ->live(),

                                    Grid::make()
                                        ->schema([


                                            Select::make('header_config.logo_position')
                                                ->label('Logo Position')
                                                ->options([
                                                    'center' => 'Center (Logo above text)',
                                                    'center-vertical' => 'Center Vertically (In middle of header)',
                                                    'left' => 'Left (Logo and text side by side)',
                                                    'right' => 'Right (Logo and text side by side)'
                                                ])
                                                ->default('center')
                                                ->reactive(),
                                            Select::make('header_config.logo_height')
                                                ->label('Logo Size')
                                                ->options([
                                                    '80px' => 'Small (80px)',
                                                    '100px' => 'Medium (100px)',
                                                    '120px' => 'Large (120px)',
                                                    '150px' => 'Extra Large (150px)',
                                                    '180px' => 'XXL (180px)'
                                                ])
                                                ->default('120px')
                                                ->helperText('Choose logo size'),

                                            Select::make('header_config.spacing.gap')
                                                ->label('Logo Text Spacing')
                                                ->options([
                                                    '1rem' => 'Tight (16px)',
                                                    '1.5rem' => 'Small (24px)',
                                                    '2rem' => 'Medium (32px)',
                                                    '2.5rem' => 'Large (40px)',
                                                    '3rem' => 'Extra Large (48px)'
                                                ])
                                                ->default('2rem')
                                                ->helperText('Space between logo and text'),

                                            Select::make('header_config.logo_margin')
                                                ->label('Logo Spacing')
                                                ->options([
                                                    'tight' => 'Tight',
                                                    'normal' => 'Normal',
                                                    'relaxed' => 'Relaxed'
                                                ])
                                                ->default('normal')
                                                ->visible(fn(Get $get) => $get('header_config.show_logo')),
                                        ])
                                        ->columns(2)
                                        ->visible(fn(Get $get) => $get('header_config.show_logo')),
                                ])
                                ->columns(1)
                                ->collapsible(),

                            Section::make('Typography & Spacing Controls')
                                ->schema([
                                    // School Name Typography
                                    Section::make('School Name')
                                        ->schema([
                                            Select::make('header_config.typography.school_name.font_size')
                                                ->label('Font Size')
                                                ->options([
                                                    '1.25rem' => 'Small',
                                                    '1.5rem' => 'Medium',
                                                    '1.75rem' => 'Large',
                                                    '2rem' => 'Extra Large'
                                                ])
                                                ->default('1.5rem'),

                                            Select::make('header_config.typography.school_name.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1' => 'Extra Compact',
                                                    '1.2' => 'Compact',
                                                    '1.4' => 'Normal',
                                                    '1.6' => 'Relaxed'
                                                ])
                                                ->default('1.2'),

                                            Select::make('header_config.typography.school_name.margin_bottom')
                                                ->label('Bottom Spacing')
                                                ->options([
                                                    '0.5rem' => 'Tight',
                                                    '0.75rem' => 'Normal',
                                                    '1rem' => 'Relaxed'
                                                ])
                                                ->default('0.75rem'),

                                            Select::make('header_config.typography.school_name.text_case')
                                                ->label('Text Case')
                                                ->options([
                                                    'none' => 'None (Default)',
                                                    'uppercase' => 'Uppercase',
                                                    'lowercase' => 'Lowercase',
                                                    'capitalize' => 'Sentence Case',
                                                    'camel-case' => 'Camel Case (Manual Styling)'
                                                ])
                                                ->default('none'),
                                            Select::make('header_config.typography.school_name.weight')
                                                ->label('School Name Weight')
                                                ->options([
                                                    '500' => 'Medium',
                                                    '600' => 'Semi Bold',
                                                    '700' => 'Bold',
                                                    '800' => 'Extra Bold'
                                                ])
                                                ->default('700'),
                                        ])->columns(4),

                                    // Address Typography
                                    Section::make('Address')
                                        ->schema([
                                            Select::make('header_config.typography.address.font_size')
                                                ->label('Font Size')
                                                ->options([
                                                    '0.75rem' => 'Small',
                                                    '0.875rem' => 'Medium',
                                                    '1rem' => 'Large'
                                                ])
                                                ->default('0.875rem'),

                                            Select::make('header_config.typography.address.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1.2' => 'Compact',
                                                    '1.4' => 'Normal',
                                                    '1.6' => 'Relaxed'
                                                ])
                                                ->default('1.4'),

                                            Select::make('header_config.typography.address.margin_bottom')
                                                ->label('Bottom Spacing')
                                                ->options([
                                                    '0.25rem' => 'Tight',
                                                    '0.5rem' => 'Normal',
                                                    '0.75rem' => 'Relaxed'
                                                ])
                                                ->default('0.5rem')
                                        ])->columns(3),

                                    // Contact Info Typography
                                    Section::make('Contact Information')
                                        ->schema([
                                            Select::make('header_config.typography.contact.font_size')
                                                ->label('Font Size')
                                                ->options([
                                                    '0.75rem' => 'Small',
                                                    '0.875rem' => 'Medium',
                                                    '1rem' => 'Large'
                                                ])
                                                ->default('0.875rem'),

                                            Select::make('header_config.typography.contact.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1.2' => 'Compact',
                                                    '1.4' => 'Normal',
                                                    '1.6' => 'Relaxed'
                                                ])
                                                ->default('1.4'),

                                            Select::make('header_config.typography.contact.margin_bottom')
                                                ->label('Bottom Spacing')
                                                ->options([
                                                    '0.25rem' => 'Tight',
                                                    '0.5rem' => 'Normal',
                                                    '0.75rem' => 'Relaxed'
                                                ])
                                                ->default('0.5rem'),

                                            Select::make('header_config.typography.contact.gap')
                                                ->label('Item Spacing')
                                                ->options([
                                                    '0.5rem' => 'Tight',
                                                    '0.75rem' => 'Normal',
                                                    '1rem' => 'Relaxed'
                                                ])
                                                ->default('0.75rem')
                                        ])->columns(4),

                                    // Report Title Typography  
                                    Section::make('Report Title')
                                        ->schema([
                                            Select::make('header_config.typography.report_title.font_size')
                                                ->label('Font Size')
                                                ->options([
                                                    '0.75rem' => 'smaller',
                                                    '1rem' => 'Small',
                                                    '1.25rem' => 'Medium',
                                                    '1.5rem' => 'Large'
                                                ])
                                                ->default('text-xl')
                                                ->visible(fn(Get $get) => $get('header_config.show_report_title')),

                                            Select::make('header_config.typography.report_title.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1' => 'Extra Compact (1)',
                                                    '1.1' => 'Compact (1.1)',
                                                    '1.2' => 'Normal (1.2)',
                                                    '1.3' => 'Relaxed (1.3)',
                                                    '1.4' => 'Spacious (1.4)'
                                                ]),

                                            Select::make('header_config.typography.report_title.margin')
                                                ->label('Spacing')
                                                ->options([
                                                    '0.5rem' => 'Tight',
                                                    '0.75rem' => 'Normal',
                                                    '1rem' => 'Relaxed'
                                                ])
                                                ->default('0.75rem')
                                        ])->columns(3)
                                ]),

                            Section::make('School Information')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            Toggle::make('header_config.show_school_name')
                                                ->label('Show School Name')
                                                ->default(true)
                                                ->live(),



                                            Toggle::make('header_config.show_school_address')
                                                ->label('Show School Address')
                                                ->default(true)
                                                ->live(),

                                            // Contact Information Group
                                            Toggle::make('header_config.show_school_contact')
                                                ->label('Show Contact Information')
                                                ->default(true)
                                                ->live(),

                                            Section::make()
                                                ->schema([
                                                    Toggle::make('header_config.show_school_phone')
                                                        ->label('Show Phone Number')
                                                        ->default(true)
                                                        ->live(),

                                                    TextInput::make('header_config.contact_info.phone_label')
                                                        ->label('Phone Label')
                                                        ->default('Tel')
                                                        ->visible(fn(Get $get) => $get('header_config.show_school_phone')),

                                                    Toggle::make('header_config.show_school_email')
                                                        ->label('Show Email')
                                                        ->default(true)
                                                        ->live(),

                                                    TextInput::make('header_config.contact_info.email_label')
                                                        ->label('Email Label')
                                                        ->default('Email')
                                                        ->visible(fn(Get $get) => $get('header_config.show_school_email')),


                                                    Select::make('header_config.contact_info.layout')
                                                        ->label('Contact Info Layout')
                                                        ->options([
                                                            'inline' => 'Inline (Horizontal)',
                                                            'stacked' => 'Stacked (Vertical)'
                                                        ])
                                                        ->default('inline'),
                                                ])
                                                ->columns(2)
                                                ->visible(fn(Get $get) => $get('header_config.show_school_contact')),
                                        ]),
                                ]),

                            Section::make('Report Title')
                                ->schema([
                                    Toggle::make('header_config.show_report_title')
                                        ->label('Show Report Title')
                                        ->live()
                                        ->default(true),

                                    TextInput::make('header_config.report_title.report_title')
                                        ->label('Report Title Text')
                                        ->default('STUDENT REPORT CARD')
                                        ->visible(fn(Get $get) => $get('header_config.show_report_title')),



                                ])->columns(3),

                            Section::make('Academic Information')
                                ->schema([
                                    Toggle::make('header_config.academic_info.show_session')
                                        ->label('Show Session')
                                        ->default(true)
                                        ->live(),

                                    Toggle::make('header_config.academic_info.show_term')
                                        ->label('Show Term')
                                        ->default(true)
                                        ->live(),

                                    Grid::make()
                                        ->schema([
                                            TextInput::make('header_config.academic_info.format.prefix')
                                                ->label('Prefix Text')
                                                ->placeholder('e.g., Academic Year'),

                                            TextInput::make('header_config.academic_info.format.separator')
                                                ->label('Separator')
                                                ->default(' - '),

                                            TextInput::make('header_config.academic_info.format.suffix')
                                                ->label('Suffix Text')
                                                ->placeholder('e.g., School Year'),

                                            Select::make('header_config.academic_info.styles.size')
                                                ->label('Text Size')
                                                ->options([
                                                    'text-sm' => 'Small',
                                                    'text-base' => 'Medium',
                                                    'text-lg' => 'Large'
                                                ])
                                                ->default('text-base'),

                                            Select::make('header_config.academic_info.styles.weight')
                                                ->label('Text Weight')
                                                ->options([
                                                    'font-normal' => 'Normal',
                                                    'font-medium' => 'Medium',
                                                    'font-semibold' => 'Semibold',
                                                    'font-bold' => 'Bold'
                                                ])
                                                ->default('font-normal'),

                                            Select::make('header_config.academic_info.styles.color')
                                                ->label('Text Color')
                                                ->options([
                                                    'text-gray-600' => 'Gray',
                                                    'text-black' => 'Black',
                                                    'text-primary-600' => 'Primary'
                                                ])
                                                ->default('text-gray-600'),
                                        ])
                                        ->columns(3)
                                        ->visible(
                                            fn(Get $get) =>
                                            $get('header_config.academic_info.show_session') ||
                                                $get('header_config.academic_info.show_term')
                                        )
                                ])
                                ->columns(2),

                            Section::make('Styling')
                                ->schema([
                                    ColorPicker::make('header_config.styles.background_color')
                                        ->label('Background Color'),

                                    ColorPicker::make('header_config.styles.text_color')
                                        ->label('Text Color'),

                                    Select::make('header_config.styles.padding')
                                        ->label('Padding')
                                        ->options([
                                            'p-4' => 'Small',
                                            'p-6' => 'Medium',
                                            'p-8' => 'Large'
                                        ])
                                        ->default('p-6'),

                                    Select::make('header_config.styles.margin_bottom')
                                        ->label('Bottom Spacing')
                                        ->options([
                                            'mb-4' => 'Small',
                                            'mb-6' => 'Medium',
                                            'mb-8' => 'Large'
                                        ])
                                        ->default('mb-6'),
                                ])->columns(4),
                        ]),


                        
                    Tabs\Tab::make('Student Information')
                        ->icon('heroicon-o-user-circle')
                        ->schema([
                            Section::make('Layout Settings')
                                ->description('Control the overall layout and spacing of student information sections')
                                ->schema([
                                    Select::make('student_info_config.layout')
                                        ->label('Main Layout')
                                        ->options([
                                            'single' => 'Single Column (Full Width)',
                                            'double' => 'Two Columns (Side by Side)',
                                            'triple' => 'Three Columns',
                                            'grid' => 'Grid Layout'
                                        ])
                                        ->default('single'),

                                    Select::make('student_info_config.table_arrangement')
                                        ->label('Table Arrangement')
                                        ->options([
                                            'stacked' => 'Stacked (Full Width)',
                                            'side-by-side' => 'Side by Side',
                                            'grid' => 'Grid Layout'
                                        ])
                                        ->default('stacked'),


                                    Select::make('student_info_config.default_styles.border_style')
                                        ->label('Border Style')
                                        ->options([
                                            'none' => 'No Border',
                                            'divider' => 'Divider Lines Between Rows',
                                            'full' => 'Full Border',
                                            'both' => 'Full Border with Dividers'
                                        ])
                                        ->default('divider')
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $borderColors = [
                                                'divider' => '#e5e7eb',
                                                'full' => '#e5e7eb',
                                                'both' => '#e5e7eb',
                                            ];
                                            $set('student_info_config.default_styles.border_color', $borderColors[$state] ?? null);
                                        }),

                                    Select::make('student_info_config.default_styles.background_style')
                                        ->label('Row Background Style')
                                        ->options([
                                            'none' => 'Plain Background',
                                            'striped' => 'Alternating Colors',
                                            'hover' => 'Highlight on Hover',
                                            'both' => 'Striped with Hover'
                                        ])
                                        ->default('none')
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $bgColors = [
                                                'striped' => '#f9fafb',
                                                'hover' => '#f3f4f6',
                                                'both' => '#f9fafb',
                                            ];
                                            $set('student_info_config.default_styles.stripe_color', $bgColors[$state] ?? null);
                                            $set(
                                                'student_info_config.default_styles.hover_color',
                                                in_array($state, ['hover', 'both']) ? '#f3f4f6' : null
                                            );
                                        }),

                                    ColorPicker::make('student_info_config.default_styles.border_color')
                                        ->label('Border Color')
                                        ->default('#e5e7eb'),

                                    ColorPicker::make('student_info_config.default_styles.stripe_color')
                                        ->label('Stripe Color')
                                        ->default('#f9fafb')
                                        ->visible(fn(Get $get) => in_array(
                                            $get('student_info_config.default_styles.background_style'),
                                            ['striped', 'both']
                                        )),

                                    ColorPicker::make('student_info_config.default_styles.hover_color')
                                        ->label('Hover Color')
                                        ->default('#f3f4f6')
                                        ->visible(fn(Get $get) => in_array(
                                            $get('student_info_config.default_styles.background_style'),
                                            ['hover', 'both']
                                        )),


                                    Section::make('Spacing Controls')
                                        ->schema([
                                            Group::make()->schema([
                                                Select::make('student_info_config.padding.container')  // Simplified path
                                                    ->label('Container Spacing')
                                                    ->options([
                                                        '0rem' => 'None',
                                                        '0.25rem' => 'Very Small',
                                                        '0.5rem' => 'Small',
                                                        '0.75rem' => 'Medium',
                                                        '1rem' => 'Large',
                                                    ])
                                                    ->default('0.5rem')
                                                    ->helperText('Overall section spacing'),

                                                Select::make('student_info_config.padding.grid')  // Simplified path
                                                    ->label('Grid Item Spacing')
                                                    ->options([
                                                        '0rem' => 'None',
                                                        '0.25rem' => 'Very Small',
                                                        '0.5rem' => 'Small',
                                                        '0.75rem' => 'Medium',
                                                        '1rem' => 'Large',
                                                    ])
                                                    ->default('0.3rem')
                                                    ->helperText('Space between grid items'),

                                                Select::make('student_info_config.padding.row')  // Simplified path
                                                    ->label('Row Spacing')
                                                    ->options([
                                                        '0rem' => 'None',
                                                        '0.25rem' => 'Very Small',
                                                        '0.5rem' => 'Small',
                                                        '0.75rem' => 'Medium',
                                                        '1rem' => 'Large',
                                                    ])
                                                    ->default('0.5rem')
                                                    ->helperText('Space between rows'),
                                            ])->columns(3),
                                        ]),

                                ])->columns(2),

                            Section::make('Section Styling')
                                ->schema([
                                    Select::make('student_info_config.default_styles.title_size')
                                        ->label('Default Title Size')
                                        ->options([
                                            'text-sm' => 'Small',
                                            'text-base' => 'Medium',
                                            'text-lg' => 'Large',
                                            'text-xl' => 'Extra Large'
                                        ])
                                        ->default('text-base'),

                                    Select::make('student_info_config.default_styles.label_size')
                                        ->label('Default Label Size')
                                        ->options([
                                            'text-xs' => 'Extra Small',
                                            'text-sm' => 'Small',
                                            'text-base' => 'Medium',
                                        ])
                                        ->default('text-sm'),

                                    Select::make('student_info_config.default_styles.value_size')
                                        ->label('Default Value Size')
                                        ->options([
                                            'text-xs' => 'Extra Small',
                                            'text-sm' => 'Small',
                                            'text-base' => 'Medium',
                                        ])
                                        ->default('text-sm'),

                                    Select::make('student_info_config.default_styles.spacing')
                                        ->label('Row Spacing')
                                        ->options([
                                            'py-1' => 'Compact',
                                            'py-1.5' => 'Normal',
                                            'py-2' => 'Relaxed',
                                        ])
                                        ->default('py-1.5'),

                                    ColorPicker::make('student_info_config.default_styles.label_color')
                                        ->label('Label Color')
                                        ->default('#4B5563'), // gray-600

                                    ColorPicker::make('student_info_config.default_styles.value_color')
                                        ->label('Value Color')
                                        ->default('#111827'), // gray-900

                                ])->columns(3)
                                ->collapsed(),


                            Section::make('Sections')
                                ->schema([
                                    Repeater::make('student_info_config.sections')
                                        ->schema([
                                            Group::make()
                                                ->schema([
                                                    TextInput::make('title')
                                                        // ->required()
                                                        ->placeholder('e.g., Student Information, Attendance Record')
                                                        ->helperText('A descriptive name for this section'),

                                                    TextInput::make('key')
                                                        // ->required()
                                                        ->placeholder('e.g., basic_info, attendance')
                                                        ->helperText('A unique identifier for this section (use snake_case)'),

                                                    TextInput::make('order')
                                                        ->integer()
                                                        ->default(fn($get) => $get('../../sections') ? count($get('../../sections')) : 0)
                                                        ->hidden()
                                                ])
                                                ->columns(2),

                                            Toggle::make('enabled')
                                                ->label('Show Section')
                                                ->default(true)
                                                ->helperText('Toggle to show/hide this section in the report'),

                                            Select::make('layout')
                                                ->options([
                                                    'table' => 'Table Layout',
                                                    'grid' => 'Grid Layout',
                                                    'flex' => 'Flexible Layout'
                                                ])
                                                ->default('table')
                                                ->helperText('How the fields will be arranged in this section'),

                                            Repeater::make('fields')
                                                ->label('Section Fields')
                                                ->schema([
                                                    Group::make()
                                                        ->schema([
                                                            TextInput::make('label')
                                                                // ->required()
                                                                ->placeholder('e.g., Student Name, Admission No')
                                                                ->helperText('Display label for this field'),

                                                            Select::make('field_type')
                                                                ->label('Field Type')
                                                                ->options([
                                                                    'admission' => 'Admission Field',
                                                                    'student' => 'Student Field',
                                                                    'term_summary' => 'Term Summary Field'
                                                                ])
                                                                ->reactive()
                                                                ->afterStateUpdated(fn(Set $set) => $set('admission_column', null)),

                                                            Select::make('admission_column')
                                                                ->label('Map to Field')
                                                                ->options(function (Get $get) {
                                                                    if ($get('field_type') === 'term_summary') {
                                                                        return \App\Enums\TermSummaryFields::FIELDS;
                                                                    }

                                                                    if ($get('field_type') === 'student') {
                                                                        return collect(Schema::getColumnListing('students'))
                                                                            ->filter(fn($column) => !in_array($column, [
                                                                                'id',
                                                                                'created_at',
                                                                                'updated_at',
                                                                                'school_id'
                                                                            ]))
                                                                            ->mapWithKeys(fn($column) => [
                                                                                $column => 'Student: ' . str($column)
                                                                                    ->replace('_', ' ')
                                                                                    ->title()
                                                                            ]);
                                                                    }

                                                                    return collect(Schema::getColumnListing('admissions'))
                                                                        ->filter(fn($column) => !in_array($column, [
                                                                            'id',
                                                                            'created_at',
                                                                            'updated_at',
                                                                            'school_id',
                                                                            'status_id',
                                                                            'academic_session_id'
                                                                        ]))
                                                                        ->mapWithKeys(fn($column) => [
                                                                            $column => 'Admission: ' . str($column)
                                                                                ->replace('_', ' ')
                                                                                ->title()
                                                                        ]);
                                                                })
                                                                ->searchable()
                                                                ->visible(fn(Get $get) => filled($get('field_type')))
                                                                ->reactive()
                                                                ->afterStateUpdated(function ($state, Set $set) {
                                                                    // Automatically set the key based on selected column
                                                                    $set('key', $state);
                                                                }),



                                                            TextInput::make('key')
                                                                ->disabled()
                                                                ->dehydrated()
                                                                ->helperText('Field key is automatically set based on admission field'),

                                                            TextInput::make('order')
                                                                ->integer()
                                                                ->default(fn($get) => $get('../../fields') ? count($get('../../fields')) : 0)
                                                                ->hidden(),

                                                            Select::make('width')
                                                                ->options([
                                                                    'full' => 'Full Width',
                                                                    'half' => 'Half Width',
                                                                    'third' => 'One Third'
                                                                ])
                                                                ->default('half')
                                                                ->helperText('Control how much space this field occupies'),
                                                        ])->columns(3),


                                                    Toggle::make('enabled')
                                                        ->label('Show Field')
                                                        ->default(true)
                                                        ->helperText('Toggle to show/hide this field'),
                                                ])
                                                ->collapsible()
                                                ->orderColumn('order')
                                                ->columns(1)
                                                ->addActionLabel('Add New Field')
                                                ->itemLabel(fn(array $state): ?string =>
                                                $state['label'] ?? ($state['key'] ?? 'New Field')),

                                            Select::make('width')
                                                ->options([
                                                    'full' => 'Full Width',
                                                    '1/2' => 'Half Width',
                                                    '1/3' => 'One Third',
                                                    '2/3' => 'Two Thirds'
                                                ])
                                                ->default('full')
                                                ->helperText('Control the width of the entire section'),

                                            Select::make('columns')
                                                ->options([
                                                    1 => '1 Column',
                                                    2 => '2 Columns',
                                                    3 => '3 Columns',
                                                    4 => '4 Columns'
                                                ])
                                                ->default(2)
                                                ->helperText('Number of columns for grid layout'),

                                            Section::make('Section Styling')
                                                ->schema([
                                                    Toggle::make('use_custom_styles')
                                                        ->label('Use Custom Styles')
                                                        ->default(false)
                                                        ->reactive(),

                                                    Grid::make()
                                                        ->schema([
                                                            Select::make('title_size')
                                                                ->label('Title Size')
                                                                ->options([
                                                                    'text-sm' => 'Small',
                                                                    'text-base' => 'Medium',
                                                                    'text-lg' => 'Large',
                                                                    'text-xl' => 'Extra Large'
                                                                ]),

                                                            Select::make('label_size')
                                                                ->label('Label Size')
                                                                ->options([
                                                                    'text-xs' => 'Extra Small',
                                                                    'text-sm' => 'Small',
                                                                    'text-base' => 'Medium',
                                                                ]),

                                                            Select::make('value_size')
                                                                ->label('Value Size')
                                                                ->options([
                                                                    'text-xs' => 'Extra Small',
                                                                    'text-sm' => 'Small',
                                                                    'text-base' => 'Medium',
                                                                ]),

                                                            Select::make('spacing')
                                                                ->label('Row Spacing')
                                                                ->options([
                                                                    'py-1' => 'Compact',
                                                                    'py-1.5' => 'Normal',
                                                                    'py-2' => 'Relaxed',
                                                                ]),

                                                            ColorPicker::make('label_color')
                                                                ->label('Label Color'),

                                                            ColorPicker::make('value_color')
                                                                ->label('Value Color'),
                                                        ])
                                                        ->columns(3)
                                                        ->visible(fn(Get $get) => $get('use_custom_styles')),

                                                    // NEW/UPDATED: Border Controls
                                                    Select::make('student_info_config.default_styles.border_style')
                                                        ->label('Border Style')
                                                        ->options([
                                                            'none' => 'No Border',
                                                            'divider' => 'Divider Lines Between Rows',
                                                            'full' => 'Full Border Around Section',
                                                            'both' => 'Full Border with Dividers'
                                                        ])
                                                        ->default('divider'),

                                                    // NEW/UPDATED: Background Controls
                                                    Select::make('student_info_config.default_styles.background_style')
                                                        ->label('Row Background Style')
                                                        ->options([
                                                            'none' => 'Plain Background',
                                                            'striped' => 'Alternating Colors',
                                                            'hover' => 'Highlight on Hover',
                                                            'both' => 'Striped with Hover'
                                                        ])
                                                        ->default('none'),
                                                ])->collapsible(),

                                        ])
                                        ->collapsed()
                                        ->orderColumn('order')
                                        ->addActionLabel('Add New Section')
                                        ->itemLabel(fn(array $state): ?string =>
                                        $state['title'] ?? 'New Section')
                                        ->columnSpanFull(),
                                ])->collapsed(),


                        ]),

                    Tabs\Tab::make('Grade Table')
                        ->schema([
                            Section::make('Table Layout')
                                ->description('Configure the overall appearance and layout of the grade table')
                                ->schema([
                                    Toggle::make('grade_table_config.enabled')
                                        ->label('Show Grade Table')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Enable or disable the entire grade table section'),

                                    TextInput::make('grade_table_config.title')
                                        ->label('Table Title')
                                        ->default('Academic Performance')
                                        ->placeholder('e.g., Academic Performance Report')
                                        ->helperText('The heading text shown above the grade table')
                                        ->visible(fn(Get $get) => $get('grade_table_config.enabled')),

                                    Toggle::make('grade_table_config.show_title')
                                        ->label('Show Title')
                                        ->default(true)
                                        ->visible(fn(Get $get) => $get('grade_table_config.enabled'))
                                        ->helperText('Toggle visibility of the table title'),

                                    Select::make('grade_table_config.spacing')
                                        ->label('Table Spacing')
                                        ->options([
                                            'compact' => 'Compact (Less space)',
                                            'normal' => 'Normal (Standard spacing)',
                                            'relaxed' => 'Relaxed (More space)'
                                        ])
                                        ->default('normal')
                                        ->helperText('Control the amount of spacing around and within the table'),

                                    ColorPicker::make('grade_table_config.background')
                                        ->label('Table Background')
                                        ->helperText('Choose a background color for the entire table section'),
                                    Select::make('grade_table_config.layout.margin')
                                        ->label('Bottom Margin')
                                        ->options([
                                            '1.5rem' => 'Small (8px)',
                                            '1.75rem' => 'Medium (16px)',
                                            '2rem' => 'Large (24px)',
                                            '2.5rem' => 'Extra Large (32px)'
                                        ])
                                        ->default('mb-6')
                                        ->helperText('Space below the table'),
                                    Select::make('grade_table_config.layout.rounded')
                                        ->label('Corner Rounding')
                                        ->options([
                                            'none' => 'No Rounding',
                                            '0.5rem' => 'Slight Rounding',
                                            '0.8rem' => 'Medium Rounding',
                                            '1rem' => 'Large Rounding'
                                        ])
                                        ->default('rounded-lg')
                                        ->helperText('Roundness of container corners'),
                                ])->columns(2),

                            Section::make('Grade Color Configuration')
                                ->description('Configure colors for different grade ranges')
                                ->schema([
                                    Grid::make(2)->schema([
                                        Section::make('Grade Scale Colors')
                                            ->schema([
                                                Group::make()->schema([
                                                    ColorPicker::make('grade_table_config.colors.excellent')
                                                        ->label('Excellent (70-100%)')
                                                        ->default('#15803d'),

                                                    ColorPicker::make('grade_table_config.colors.very_good')
                                                        ->label('Very Good (60-69%)')
                                                        ->default('#166534'),

                                                    ColorPicker::make('grade_table_config.colors.good')
                                                        ->label('Good (50-59%)')
                                                        ->default('#0369a1'),

                                                    ColorPicker::make('grade_table_config.colors.poor')
                                                        ->label('Poor (40-49%)')
                                                        ->default('#d97706'),

                                                    ColorPicker::make('grade_table_config.colors.fail')
                                                        ->label('Fail (0-39%)')
                                                        ->default('#dc2626'),
                                                ])->columns(5),
                                            ]),

                                        Section::make('Color Application')
                                            ->schema([
                                                Toggle::make('grade_table_config.color_settings.apply_to_subject')
                                                    ->label('Color Subject Names')
                                                    ->default(true)
                                                    ->helperText('Apply grade colors to subject names'),

                                                Toggle::make('grade_table_config.color_settings.apply_to_total')
                                                    ->label('Color Total Scores')
                                                    ->default(true)
                                                    ->helperText('Apply grade colors to total scores'),

                                                Toggle::make('grade_table_config.color_settings.apply_to_grade')
                                                    ->label('Color Grade Letters')
                                                    ->default(true)
                                                    ->helperText('Apply grade colors to grade letters'),

                                                Toggle::make('grade_table_config.color_settings.apply_to_remark')
                                                    ->label('Color Remarks')
                                                    ->default(true)
                                                    ->helperText('Apply grade colors to remarks'),
                                            ])
                                    ]),
                                ])
                                ->collapsed(),
                            // Add to the Grade Table tab configuration
                            Section::make('Font & Sizing')
                                ->schema([
                                    TextInput::make('grade_table_config.font_family')
                                        ->label('Font Family')
                                        ->placeholder('Arial, sans-serif')
                                        ->default('system-ui, -apple-system, sans-serif')
                                        ->helperText('Custom font family for the table'),

                                    Select::make('grade_table_config.font_size')
                                        ->label('Base Font Size')
                                        ->options([
                                            '0.75rem' => 'Extra Small (12px)',
                                            '0.875rem' => 'Small (14px)',
                                            '1rem' => 'Medium (16px)',
                                            '1.125rem' => 'Large (18px)'
                                        ])
                                        ->default('0.875rem'),

                                    Select::make('grade_table_config.line_height')
                                        ->label('Line Height')
                                        ->options([
                                            '1' => 'Tight',
                                            '1.25' => 'Normal',
                                            '1.5' => 'Relaxed'
                                        ])
                                        ->default('1.25'),

                                    // Header specific font controls
                                    Select::make('grade_table_config.header.font_size')
                                        ->label('Header Font Size')
                                        ->options([
                                            '0.75rem' => 'Extra Small (12px)',
                                            '0.875rem' => 'Small (14px)',
                                            '1rem' => 'Medium (16px)'
                                        ])
                                        ->default('0.875rem'),

                                    // Row specific font controls  
                                    Select::make('grade_table_config.rows.font_size')
                                        ->label('Row Font Size')
                                        ->options([
                                            '0.75rem' => 'Extra Small (12px)',
                                            '0.875rem' => 'Small (14px)',
                                            '1rem' => 'Medium (16px)'
                                        ])
                                        ->default('0.875rem'),
                                    Select::make('grade_table_config.rows.font_weight')
                                        ->label('Font Weight')
                                        ->options([
                                            '400' => 'Normal (400)',
                                            '500' => 'Medium (500)',
                                            '600' => 'Semi Bold (600)',
                                            '700' => 'Bold (700)'
                                        ])
                                        ->default('font-semibold')
                                        ->helperText('Thickness of the rows text'),
                                ])->columns(3)
                                ->collapsed(),

                            Section::make('Column Visibility')
                                ->schema([
                                    Toggle::make('grade_table_config.columns.subject.show')
                                        ->label('Show Subject Column')
                                        ->default(true),

                                    // Repeat for each column type
                                    Toggle::make('grade_table_config.columns.total.enabled')
                                        ->label('Show Total Column')
                                        ->default(true),

                                    Toggle::make('grade_table_config.columns.grade.enabled')
                                        ->label('Show Grade Column')
                                        ->default(true),

                                    Toggle::make('grade_table_config.columns.remark.enabled')
                                        ->label('Show Remark Column')
                                        ->default(true),
                                ])->columns(4)
                                ->collapsed(),

                            Section::make('Striping & Spacing')
                                ->schema([
                                    ColorPicker::make('grade_table_config.rows.stripe_color')
                                        ->label('Stripe Color')
                                        ->default('#f9fafb'),

                                    TextInput::make('grade_table_config.cell_padding')
                                        ->label('Cell Padding')
                                        ->placeholder('0.5rem')
                                        ->default('0.5rem')
                                        ->helperText('Padding inside table cells (in rem)'),
                                ])->columns(2)
                                ->collapsed(),

                            Section::make('Subject Column')
                                ->description('Configure the leftmost column that displays subject names')
                                ->schema([
                                    TextInput::make('grade_table_config.subject_column.title')
                                        ->label('Column Title')
                                        ->default('Subject')
                                        ->placeholder('e.g., Subject, Course, Module')
                                        ->helperText('Header text for the subject column'),

                                    Select::make('grade_table_config.subject_column.width')
                                        ->label('Column Width')
                                        ->options([
                                            'w-32' => 'Narrow (128px)',
                                            'w-48' => 'Normal (192px)',
                                            'w-64' => 'Wide (256px)'
                                        ])
                                        ->default('w-48')
                                        ->helperText('Control how wide the subject column should be'),

                                    Select::make('grade_table_config.subject_column.align')
                                        ->label('Text Alignment')
                                        ->options([
                                            'text-left' => 'Left (Default)',
                                            'text-center' => 'Center',
                                            'text-right' => 'Right'
                                        ])
                                        ->default('text-left')
                                        ->helperText('How subject names should be aligned within the column'),
                                ])->columns(3)
                                ->collapsed(),

                            Section::make('Assessment Columns')
                                ->description('Configure the columns for continuous assessment scores and examinations')
                                ->schema([
                                    Repeater::make('grade_table_config.assessment_columns')
                                        ->schema([
                                            TextInput::make('name')
                                                // ->required()
                                                ->label('Column Name')
                                                ->placeholder('e.g., CA 1, Mid-Term, Final Exam')
                                                ->helperText('Display name for this assessment column'),

                                            TextInput::make('key')
                                                // ->required()
                                                ->label('Column Key')
                                                ->placeholder('e.g., ca1, midterm, final_exam')
                                                ->helperText('Unique identifier for this column (use lowercase with underscores)'),

                                            TextInput::make('max_score')
                                                ->numeric()
                                                ->label('Maximum Score')
                                                ->default(100)
                                                ->placeholder('e.g., 100')
                                                ->helperText('Maximum possible score for this assessment'),

                                            TextInput::make('weight')
                                                ->numeric()
                                                ->label('Weight (%)')
                                                ->default(0)
                                                ->placeholder('e.g., 30')
                                                ->helperText('Percentage weight in final grade calculation'),

                                            Toggle::make('show_max_score')
                                                ->label('Show Max Score')
                                                ->default(true)
                                                ->helperText('Display maximum possible score in column header'),

                                            Select::make('width')
                                                ->label('Column Width')
                                                ->options([
                                                    'w-16' => 'Narrow (64px)',
                                                    'w-20' => 'Normal (80px)',
                                                    'w-24' => 'Wide (96px)'
                                                ])
                                                ->default('w-20')
                                                ->helperText('Width of this assessment column'),
                                        ])
                                        ->orderColumn('order')
                                        ->collapsed()
                                        ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'New Assessment')
                                        ->addActionLabel('Add Assessment Column')
                                        ->helperText('Add multiple assessment columns like CA tests, assignments, and exams')
                                        ->columns(3),
                                ])
                                ->collapsed(),

                            Section::make('Result Columns')
                                ->description('Configure the summary columns that appear after assessment scores')
                                ->schema([
                                    Toggle::make('grade_table_config.show_total')
                                        ->label('Show Total Column')
                                        ->default(true)
                                        ->helperText('Display column showing total marks'),

                                    Toggle::make('grade_table_config.show_grade')
                                        ->label('Show Grade Column')
                                        ->default(true)
                                        ->helperText('Display column showing letter grade'),

                                    Toggle::make('grade_table_config.show_remark')
                                        ->label('Show Remark Column')
                                        ->default(true)
                                        ->helperText('Display column showing performance remark'),

                                    Select::make('grade_table_config.total_column.width')
                                        ->label('Total Column Width')
                                        ->options([
                                            'w-16' => 'Narrow (64px)',
                                            'w-20' => 'Normal (80px)',
                                            'w-24' => 'Wide (96px)'
                                        ])
                                        ->default('w-20')
                                        ->helperText('Width of the total marks column'),

                                    Select::make('grade_table_config.grade_column.width')
                                        ->label('Grade Column Width')
                                        ->options([
                                            'w-16' => 'Narrow (64px)',
                                            'w-20' => 'Normal (80px)',
                                            'w-24' => 'Wide (96px)'
                                        ])
                                        ->default('w-16')
                                        ->helperText('Width of the grade column'),

                                    Select::make('grade_table_config.remark_column.width')
                                        ->label('Remark Column Width')
                                        ->options([
                                            'w-32' => 'Narrow (128px)',
                                            'w-40' => 'Normal (160px)',
                                            'w-48' => 'Wide (192px)'
                                        ])
                                        ->default('w-32')
                                        ->helperText('Width of the remark column'),
                                ])->columns(3)
                                ->collapsed(),
                            Section::make('Table Border')
                                ->description('Configure the table border styling')
                                ->schema([
                                    Select::make('grade_table_config.border.style')
                                        ->label('Border Style')
                                        ->options([
                                            'none' => 'No Border',
                                            'single' => 'Single Line',
                                            'double' => 'Double Line',
                                            'premium' => 'Premium Border',
                                            'modern' => 'Modern Shadow'
                                        ])
                                        ->default('single')
                                        ->reactive(),

                                    ColorPicker::make('grade_table_config.border.color')
                                        ->label('Border Color')
                                        ->default('#e5e7eb'),

                                    Select::make('grade_table_config.border.width')
                                        ->label('Border Width')
                                        ->options([
                                            '1px' => 'Thin (1px)',
                                            '2px' => 'Medium (2px)',
                                            '3px' => 'Thick (3px)'
                                        ])
                                        ->default('1px'),

                                    Select::make('grade_table_config.border.radius')
                                        ->label('Corner Radius')
                                        ->options([
                                            '0' => 'Square',
                                            '0.375rem' => 'Slightly Rounded',
                                            '0.5rem' => 'Rounded',
                                            '0.75rem' => 'More Rounded'
                                        ])
                                        ->default('0.375rem')
                                ])->columns(4)
                                ->collapsed(),

                            Section::make('Header Styling')
                                ->description('Customize the appearance of the table header row')
                                ->schema([
                                    ColorPicker::make('grade_table_config.header.background')
                                        ->label('Background Color')
                                        ->default('#f3f4f6')
                                        ->helperText('Background color for the header row'),

                                    ColorPicker::make('grade_table_config.header.text_color')
                                        ->label('Text Color')
                                        ->default('#111827')
                                        ->helperText('Color of the text in header cells'),

                                    Select::make('grade_table_config.header.font_weight')
                                        ->label('Font Weight')
                                        ->options([
                                            'font-normal' => 'Normal (400)',
                                            'font-medium' => 'Medium (500)',
                                            'font-semibold' => 'Semi Bold (600)',
                                            'font-bold' => 'Bold (700)'
                                        ])
                                        ->default('font-semibold')
                                        ->helperText('Thickness of the header text'),

                                    Select::make('grade_table_config.header.padding')
                                        ->label('Padding')
                                        ->options([
                                            '0.25rem' => 'Tight (4px)',
                                            '0.5rem' => 'Normal (8px)',
                                            '0.75rem' => 'Relaxed (12px)',
                                            '1rem' => 'Spacious (16px)'
                                        ])
                                        ->default('0.5rem')  // Changed from 'p-2' to '0.5rem'
                                        ->helperText('Space inside header cells'),
                                ])->columns(4)
                                ->collapsed(),

                            Section::make('Footer Configuration')
                                ->description('Configure the summary section at the bottom of the grade table')
                                ->schema([
                                    Toggle::make('grade_table_config.footer.enabled')
                                        ->label('Show Footer')
                                        ->default(true)
                                        ->live()
                                        ->helperText('Enable or disable the footer section'),

                                    Toggle::make('grade_table_config.footer.show_total_score')
                                        ->label('Show Total Score')
                                        ->default(true)
                                        ->visible(fn(Get $get) => $get('grade_table_config.footer.enabled'))
                                        ->helperText('Display sum of all subject scores'),

                                    Toggle::make('grade_table_config.footer.show_average')
                                        ->label('Show Average')
                                        ->default(true)
                                        ->visible(fn(Get $get) => $get('grade_table_config.footer.enabled'))
                                        ->helperText('Display overall average score'),

                                    Toggle::make('grade_table_config.footer.show_position')
                                        ->label('Show Position')
                                        ->default(true)
                                        ->visible(fn(Get $get) => $get('grade_table_config.footer.enabled'))
                                        ->helperText('Display student\'s position in class'),

                                    ColorPicker::make('grade_table_config.footer.background')
                                        ->label('Background Color')
                                        ->default('#f3f4f6')
                                        ->visible(fn(Get $get) => $get('grade_table_config.footer.enabled'))
                                        ->helperText('Background color for the footer section'),

                                    Select::make('grade_table_config.footer.border')
                                        ->label('Border Style')
                                        ->options([
                                            'border-t' => 'Single Top (1px)',
                                            'border-t-2' => 'Double Top (2px)',
                                            'border-t-4' => 'Thick Top (4px)'
                                        ])
                                        ->default('border-t-2')
                                        ->visible(fn(Get $get) => $get('grade_table_config.footer.enabled'))
                                        ->helperText('Style of the border above the footer'),
                                ])->columns(3)
                                ->collapsed(),
                        ]),

                    Tabs\Tab::make('Comments & Activities')
                        ->schema([
                            Section::make('Comments Configuration')
                                ->description('Configure teacher and principal comment sections')
                                ->schema([
                                    Toggle::make('comments_config.enabled')
                                        ->label('Enable Comments Section')
                                        ->default(true)
                                        ->reactive(),

                                    Grid::make(2)
                                        ->schema([
                                            Select::make('comments_config.layout')
                                                ->label('Layout Style')
                                                ->options([
                                                    'stacked' => 'Stacked (Full Width)',
                                                    'side-by-side' => 'Side by Side',
                                                    'grid' => 'Grid Layout'
                                                ])
                                                ->default('stacked')
                                                ->reactive()
                                                ->visible(fn(Get $get) => $get('comments_config.enabled')),

                                            Select::make('comments_config.spacing')
                                                ->label('Section Spacing')
                                                ->options([
                                                    'tight' => 'Tight',
                                                    'normal' => 'Normal',
                                                    'relaxed' => 'Relaxed'
                                                ])
                                                ->default('normal')
                                                ->visible(fn(Get $get) => $get('comments_config.enabled')),
                                        ]),

                                    Repeater::make('comments_config.sections')
                                        ->label('Comment Sections')
                                        ->schema([
                                            TextInput::make('title')
                                                ->label('Section Title')
                                                ->default(function (callable $get, ?string $state) {
                                                    $itemIndex = count($get('../sections') ?? []);
                                                    return $itemIndex === 0 ? "Class Teacher's Comment" : "Principal's Comment";
                                                }),

                                            Grid::make(2)->schema([
                                                Toggle::make('enabled')
                                                    ->label('Enable Section')
                                                    ->default(true),

                                                Toggle::make('show_signatures')
                                                    ->label('Show Signatures')
                                                    ->default(true)
                                                    ->reactive(),
                                            ]),

                                            // Signature toggles grid (only shown when signatures are enabled)
                                            Grid::make(2)
                                                ->schema([
                                                    Toggle::make('signature_fields.show_digital')
                                                        ->label('Enable Digital Signature')
                                                        ->default(true)
                                                        ->reactive(),

                                                    Toggle::make('signature_fields.show_manual')
                                                        ->label('Enable Manual Signature')
                                                        ->default(false)
                                                        ->reactive(),

                                                    Toggle::make('signature_fields.show_name')
                                                        ->label('Show Name')
                                                        ->default(true),

                                                    Toggle::make('signature_fields.show_date')
                                                        ->label('Show Date')
                                                        ->default(true),
                                                ])
                                                ->visible(fn(Get $get) => $get('show_signatures')),

                                            // Signature styling options
                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('signature_fields.alignment')
                                                        ->label('Signature Alignment')
                                                        ->options([
                                                            'left' => 'Left',
                                                            'center' => 'Center',
                                                            'right' => 'Right'
                                                        ])
                                                        ->default('right'),

                                                    TextInput::make('signature_fields.width')
                                                        ->label('Signature Width')
                                                        ->placeholder('200px')
                                                        ->default('200px'),
                                                ])
                                                ->visible(fn(Get $get) => $get('show_signatures')),
                                        ])
                                        ->defaultItems(2)
                                        ->columns(1)
                                        ->collapsed()
                                        ->itemLabel(fn(array $state): ?string => $state['title'] ?? 'New Comment Section')
                                ]),

                            Section::make('Activities Configuration')
                                ->description('Configure sections for activities and behavioral assessment')
                                ->schema([
                                    Toggle::make('activities_config.enabled')
                                        ->label('Enable Activities Section')
                                        ->default(true)
                                        ->reactive(),

                                    Grid::make(2)
                                        ->schema([
                                            Select::make('activities_config.layout')
                                                ->label('Layout Style')
                                                ->options([
                                                    'side-by-side' => 'Side by Side',
                                                    'stacked' => 'Stacked'
                                                ])
                                                ->default('side-by-side')
                                                ->reactive()
                                                ->visible(fn(Get $get) => $get('activities_config.enabled')),

                                            Select::make('activities_config.spacing')
                                                ->label('Section Spacing')
                                                ->options([
                                                    'tight' => 'Tight',
                                                    'normal' => 'Normal',
                                                    'relaxed' => 'Relaxed'
                                                ])
                                                ->default('normal')
                                                ->visible(fn(Get $get) => $get('activities_config.enabled')),
                                        ]),
                                    // Grading Scale Configuration Section 
                                    Section::make('Grading Scale')
                                        ->schema([
                                            TextInput::make('activities_config.grading_scale.title')
                                                ->label('Scale Title')
                                                ->default('Grade Scale')
                                                ->visible(fn(Get $get) =>
                                                $get('activities_config.enabled') &&
                                                    $get('activities_config.grading_scale.enabled')),

                                            Select::make('activities_config.grading_scale.position')
                                                ->label('Position')
                                                ->options([
                                                    'top' => 'Top',
                                                    'bottom' => 'Bottom'
                                                ])
                                                ->default('bottom')
                                                ->visible(fn(Get $get) =>
                                                $get('activities_config.enabled') &&
                                                    $get('activities_config.grading_scale.enabled')),
                                        ])
                                        ->collapsed()
                                        ->visible(fn(Get $get) =>
                                        $get('activities_config.enabled') &&
                                            $get('activities_config.grading_scale.enabled')),

                                    Section::make('Table Styling')
                                        ->schema([
                                            Group::make()->schema([
                                                Select::make('activities_config.table_style.font_size')
                                                    ->label('Text Size')
                                                    ->options([
                                                        '0.5rem' => 'Small (12px)',
                                                        '0.75rem' => 'Medium (14px)',
                                                        '1rem' => 'Large (16px)',
                                                        '1.125rem' => 'Extra Large (18px)'
                                                    ])
                                                    // ->required()
                                                    ->default('0.875rem'),

                                                Select::make('activities_config.table_style.cell_padding')
                                                    ->label('Cell Spacing')
                                                    ->options([
                                                        '0.5rem' => 'Compact',
                                                        '0.75rem' => 'Normal',
                                                        '1rem' => 'Relaxed'
                                                    ])
                                                    // ->required()
                                                    ->default('0.75rem'),

                                                Select::make('activities_config.table_style.row_height')
                                                    ->label('Row Height')
                                                    ->options([
                                                        '1rem' => 'Compact',
                                                        '1.5rem' => 'Normal',
                                                        '2rem' => 'Relaxed'
                                                    ])
                                                    // ->required()
                                                    ->default('2.5rem')
                                            ])->columns(3),
                                        ])->collapsible(),

                                    Repeater::make('activities_config.sections')
                                        ->label('Sections')
                                        ->schema([
                                            TextInput::make('title')
                                                ->label('Section Title')
                                                // ->required()
                                                ->helperText('E.g. Sports & Athletics, Arts & Culture, Behavioral Traits')
                                                ->placeholder('e.g., Sports & Athletics'),

                                            Toggle::make('enabled')
                                                ->label('Enable Section')
                                                ->default(true),

                                            Grid::make(2)
                                                ->schema([
                                                    Select::make('style.background')
                                                        ->label('Background Style')
                                                        ->options([
                                                            'light' => 'Light',
                                                            'white' => 'White'
                                                        ])
                                                        ->default('light'),

                                                    Toggle::make('style.shadow')
                                                        ->label('Show Shadow')
                                                        ->default(true),
                                                ]),

                                            Repeater::make('fields')
                                                ->label('Items')
                                                ->schema([
                                                    TextInput::make('name')
                                                        ->label('Item Name')
                                                        // ->required()
                                                        ->helperText(function (Get $get) {
                                                            $sectionTitle = $get('../../title');
                                                            if (strtolower($sectionTitle) === 'behavioral traits') {
                                                                return 'E.g. Communication, Punctuality, Neatness';
                                                            }
                                                            return 'E.g. Football, Drama Club, Music Band';
                                                        }),

                                                    Toggle::make('enabled')
                                                        ->label('Show Item')
                                                        ->default(true),

                                                    Select::make('type')
                                                        ->label('Rating Type')
                                                        ->options([
                                                            'rating' => 'Star Rating',
                                                        ])
                                                        ->default('rating'),

                                                    Grid::make(2)
                                                        ->schema([
                                                            Select::make('value.rating')
                                                                ->label('Default Rating')
                                                                ->options([
                                                                    1 => '★ (Poor)',
                                                                    2 => '★★ (Fair)',
                                                                    3 => '★★★ (Good)',
                                                                    4 => '★★★★ (Very Good)',
                                                                    5 => '★★★★★ (Excellent)'
                                                                ])
                                                                ->reactive()
                                                                ->afterStateUpdated(function ($state, Set $set) {
                                                                    $performance = match ((int) $state) {
                                                                        1 => 'Poor',
                                                                        2 => 'Fair',
                                                                        3 => 'Good',
                                                                        4 => 'Very Good',
                                                                        5 => 'Excellent',
                                                                        default => 'N/A',
                                                                    };
                                                                    $set('value.performance', $performance);
                                                                }),

                                                            Select::make('style.text_color')
                                                                ->label('Rating Color')
                                                                ->options([
                                                                    'default' => 'Gray',
                                                                    'primary' => 'Blue',
                                                                    'warning' => 'Yellow',
                                                                    'success' => 'Green'
                                                                ])
                                                                ->default('warning'),
                                                        ]),

                                                    Select::make('style.alignment')
                                                        ->label('Rating Alignment')
                                                        ->options([
                                                            'left' => 'Left',
                                                            'center' => 'Center',
                                                            'right' => 'Right'
                                                        ])
                                                        ->default('center'),
                                                ])
                                                ->defaultItems(3)
                                                ->collapsed()
                                                ->reorderable()
                                                ->columnSpanFull()
                                                ->itemLabel(fn(array $state): ?string => $state['name'] ?? 'New Item'),
                                        ])
                                        ->defaultItems(3)
                                        ->collapsed()
                                        ->reorderable()
                                        ->itemLabel(fn(array $state): ?string => $state['title'] ?? 'New Section')
                                        ->visible(fn(Get $get) => $get('activities_config.enabled')),
                                ]),

                            // Add this to your form schema, alongside the activities configuration

                        ]),

                    Tabs\Tab::make('RTL & Arabic')
                        ->schema([
                            Toggle::make('rtl_config.enable_arabic')
                                ->label('Enable Arabic Text')
                                ->reactive(),

                            Select::make('rtl_config.text_direction')
                                ->label('Text Direction')
                                ->options([
                                    'ltr' => 'Left to Right (LTR)',
                                    'rtl' => 'Right to Left (RTL)',
                                ])
                                ->visible(fn(Get $get) => $get('rtl_config.enable_arabic')),

                            Select::make('rtl_config.arabic_font')
                                ->label('Arabic Font')
                                ->options([
                                    'Noto Naskh Arabic' => 'Noto Naskh Arabic',
                                    'Cairo' => 'Cairo',
                                    'Amiri' => 'Amiri'
                                ])
                                ->visible(fn(Get $get) => $get('rtl_config.enable_arabic')),

                            Section::make('Header Settings')
                                ->schema([
                                    Toggle::make('rtl_config.header.show_arabic_name')
                                        ->label('Show Arabic School Name'),

                                    // Add this new TextInput for Arabic School Name
                                    TextInput::make('rtl_config.header.arabic_name')
                                        ->label('Arabic School Name')
                                        ->placeholder('Enter school name in Arabic')
                                        // ->directionRTL()
                                        ->visible(fn(Get $get) => $get('rtl_config.header.show_arabic_name')),

                                    Select::make('rtl_config.header.arabic_name_position')
                                        ->label('Arabic Name Position')
                                        ->options([
                                            'above' => 'Above English Name',
                                            'below' => 'Below English Name',
                                            'opposite' => 'Opposite Side'
                                        ]),

                                    // Add Arabic text size option
                                    Select::make('rtl_config.header.arabic_text_size')
                                        ->label('Arabic Text Size')
                                        ->options([
                                            'text-sm' => 'Small',
                                            'text-base' => 'Medium',
                                            'text-lg' => 'Large',
                                            'text-xl' => 'Extra Large',
                                            'text-2xl' => 'Double Extra Large'
                                        ])
                                        ->default('text-xl')
                                ])
                                ->visible(fn(Get $get) => $get('rtl_config.enable_arabic')),

                            Section::make('Subject Settings')
                                ->schema([
                                    Toggle::make('rtl_config.subjects.show_arabic_names')
                                        ->label('Show Arabic Subject Names')
                                        ->reactive(),

                                    Select::make('rtl_config.subjects.display_style')
                                        ->label('Display Style')
                                        ->options([
                                            'brackets' => 'In Brackets (English) العربية',
                                            'justified' => 'Justified English | العربية',
                                            'newline' => 'New Line (English below Arabic)',
                                            'separate' => 'Separate Columns'
                                        ])
                                        ->default('brackets')
                                        ->reactive()
                                        ->visible(fn(Get $get) => $get('rtl_config.subjects.show_arabic_names')),

                                    Select::make('rtl_config.subjects.arabic_column_position')
                                        ->label('Arabic Names Position')
                                        ->options([
                                            'before' => 'Before English Names',
                                            'after' => 'After English Names',
                                            'opposite' => 'Opposite Side'
                                        ])
                                        ->visible(
                                            fn(Get $get) =>
                                            $get('rtl_config.subjects.show_arabic_names') &&
                                                $get('rtl_config.subjects.display_style') === 'separate'
                                        ),

                                    Select::make('rtl_config.subjects.arabic_text_size')
                                        ->label('Arabic Text Size')
                                        ->options([
                                            'text-xs' => 'Extra Small',
                                            'text-sm' => 'Small',
                                            'text-base' => 'Medium',
                                            'text-lg' => 'Large'
                                        ])
                                        ->default('text-sm')
                                        ->visible(fn(Get $get) => $get('rtl_config.subjects.show_arabic_names')),

                                    Select::make('rtl_config.subjects.separator')
                                        ->label('Separator Style')
                                        ->options([
                                            'brackets' => '()',
                                            'square_brackets' => '[]',
                                            'pipe' => '|',
                                            'dash' => '-',
                                            'none' => 'None'
                                        ])
                                        ->default('brackets')
                                        ->visible(
                                            fn(Get $get) =>
                                            $get('rtl_config.subjects.show_arabic_names') &&
                                                $get('rtl_config.subjects.display_style') === 'brackets'
                                        ),

                                    ColorPicker::make('rtl_config.subjects.arabic_text_color')
                                        ->label('Arabic Text Color')
                                        ->default('#374151')
                                        ->visible(fn(Get $get) => $get('rtl_config.subjects.show_arabic_names')),

                                    Toggle::make('rtl_config.subjects.bold_arabic')
                                        ->label('Bold Arabic Text')
                                        ->default(false)
                                        ->visible(fn(Get $get) => $get('rtl_config.subjects.show_arabic_names'))
                                ])
                                ->visible(fn(Get $get) => $get('rtl_config.enable_arabic'))


                        ]),
                    Tabs\Tab::make('Print Configuration')
                        ->schema([
                            Section::make('Paper Settings')
                                ->schema([
                                    Select::make('print_config.paper_size')
                                        ->label('Paper Size')
                                        ->options([
                                            'A4' => 'A4 (210mm × 297mm)',
                                            'Letter' => 'Letter (8.5" × 11")',
                                            'Legal' => 'Legal (8.5" × 14")',
                                            'A5' => 'A5 (148mm × 210mm)',
                                            'A3' => 'A3 (297mm × 420mm)',
                                        ])
                                        ->default('A4'),

                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.margins.top')
                                                ->label('Top Margin (px)')
                                                ->options([
                                                    '20' => '20px (Minimal)',
                                                    '40' => '40px (Normal)',
                                                    '60' => '60px (Relaxed)',
                                                    '80' => '80px (Loose)',
                                                    '100' => '100px (Extra Loose)'
                                                ])
                                                ->default('40'),

                                            Select::make('print_config.margins.right')
                                                ->label('Right Margin (px)')
                                                ->options([
                                                    '20' => '20px (Minimal)',
                                                    '40' => '40px (Normal)',
                                                    '60' => '60px (Relaxed)',
                                                    '80' => '80px (Loose)',
                                                    '100' => '100px (Extra Loose)'
                                                ])
                                                ->default('40'),

                                            Select::make('print_config.margins.bottom')
                                                ->label('Bottom Margin (px)')
                                                ->options([
                                                    '20' => '20px (Minimal)',
                                                    '40' => '40px (Normal)',
                                                    '60' => '60px (Relaxed)',
                                                    '80' => '80px (Loose)',
                                                    '100' => '100px (Extra Loose)'
                                                ])
                                                ->default('40'),

                                            Select::make('print_config.margins.left')
                                                ->label('Left Margin (px)')
                                                ->options([
                                                    '20' => '20px (Minimal)',
                                                    '40' => '40px (Normal)',
                                                    '60' => '60px (Relaxed)',
                                                    '80' => '80px (Loose)',
                                                    '100' => '100px (Extra Loose)'
                                                ])
                                                ->default('40'),
                                        ])
                                        ->columns(4)
                                ])
                                ->collapsible(),

                            Section::make('Header Print Settings')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.header.logo_height')
                                                ->label('Logo Height (px)')
                                                ->options([
                                                    '60' => 'Small (60px)',
                                                    '80' => 'Medium (80px)',
                                                    '100' => 'Large (100px)',
                                                    '120' => 'Extra Large (120px)'
                                                ])
                                                ->default('80'),

                                            Select::make('print_config.header.school_name.font_size')
                                                ->label('School Name Font Size')
                                                ->options([
                                                    '12' => 'Small (12px)',
                                                    '14' => 'Medium (14px)',
                                                    '16' => 'Large (16px)',
                                                    '18' => 'Extra Large (18px)'
                                                ])
                                                ->default('14'),

                                            Select::make('print_config.header.address.font_size')
                                                ->label('Address Font Size')
                                                ->options([
                                                    '9' => 'Small (9px)',
                                                    '11' => 'Medium (11px)',
                                                    '13' => 'Large (13px)'
                                                ])
                                                ->default('11'),

                                            Select::make('print_config.header.contact_info.font_size')
                                                ->label('Contact Info Font Size')
                                                ->options([
                                                    '8' => 'Small (8px)',
                                                    '10' => 'Medium (10px)',
                                                    '12' => 'Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.header.report_title.font_size')
                                                ->label('Report Title Font Size')
                                                ->options([
                                                    '10' => 'Small (10px)',
                                                    '12' => 'Medium (12px)',
                                                    '14' => 'Large (14px)',
                                                    '16' => 'Extra Large (16px)'
                                                ])
                                                ->default('12'),

                                            Select::make('print_config.header.spacing')
                                                ->label('Section Spacing')
                                                ->options([
                                                    '6' => 'Compact (6px)',
                                                    '8' => 'Normal (8px)',
                                                    '10' => 'Relaxed (10px)',
                                                    '12' => 'Loose (12px)'
                                                ])
                                                ->default('8'),
                                        ])
                                        ->columns(2),
                                ])
                                ->collapsible()
                                ->collapsed(),

                            Section::make('Student Info Print Settings')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.student_info.title.font_size')
                                                ->label('Section Title Font Size')
                                                ->options([
                                                    '8' => 'Extra Small (8px)',
                                                    '10' => 'Small (10px)',
                                                    '11' => 'Medium (11px)',
                                                    '12' => 'Large (12px)',
                                                    '14' => 'Extra Large (14px)'
                                                ])
                                                ->default('11'),

                                            Select::make('print_config.student_info.labels.font_size')
                                                ->label('Label Font Size')
                                                ->options([
                                                    '7' => 'Extra Small (7px)',
                                                    '8' => 'Small (8px)',
                                                    '9' => 'Medium (9px)',
                                                    '10' => 'Large (10px)',
                                                    '11' => 'Extra Large (11px)'
                                                ])
                                                ->default('9'),

                                            Select::make('print_config.student_info.values.font_size')
                                                ->label('Value Font Size')
                                                ->options([
                                                    '7' => 'Extra Small (7px)',
                                                    '8' => 'Small (8px)',
                                                    '9' => 'Medium (9px)',
                                                    '10' => 'Large (10px)',
                                                    '11' => 'Extra Large (11px)'
                                                ])
                                                ->default('9'),

                                            Select::make('print_config.student_info.container_padding')
                                                ->label('Container Padding')
                                                ->options([
                                                    '2' => 'Extra Tight (2px)',
                                                    '4' => 'Tight (4px)',
                                                    '6' => 'Normal (6px)',
                                                    '8' => 'Relaxed (8px)',
                                                    '10' => 'Spacious (10px)'
                                                ])
                                                ->default('6'),

                                            Select::make('print_config.student_info.section_gap')
                                                ->label('Gap Between Sections')
                                                ->options([
                                                    '2' => 'Extra Tight (2px)',
                                                    '4' => 'Tight (4px)',
                                                    '6' => 'Normal (6px)',
                                                    '8' => 'Relaxed (8px)',
                                                    '10' => 'Spacious (10px)'
                                                ])
                                                ->default('6'),

                                            Select::make('print_config.student_info.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1' => 'Extra Compact (1)',
                                                    '1.1' => 'Compact (1.1)',
                                                    '1.2' => 'Normal (1.2)',
                                                    '1.3' => 'Relaxed (1.3)',
                                                    '1.4' => 'Spacious (1.4)'
                                                ])
                                                ->default('1.1'),

                                            Select::make('print_config.student_info.title_margin')
                                                ->label('Title Margin')
                                                ->options([
                                                    '2' => 'Extra Small (2px)',
                                                    '4' => 'Small (4px)',
                                                    '6' => 'Medium (6px)',
                                                    '8' => 'Large (8px)',
                                                    '10' => 'Extra Large (10px)'
                                                ])
                                                ->default('4'),

                                            Select::make('print_config.student_info.row_spacing')
                                                ->label('Row Spacing')
                                                ->options([
                                                    '1' => 'Extra Tight (1px)',
                                                    '2' => 'Tight (2px)',
                                                    '3' => 'Normal (3px)',
                                                    '4' => 'Relaxed (4px)',
                                                    '5' => 'Spacious (5px)'
                                                ])
                                                ->default('2')
                                        ])
                                        ->columns(2)
                                ])->collapsed(),

                            Section::make('Grades Table Print Settings')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.grades_table.header.font_size')
                                                ->label('Header Font Size')
                                                ->options([
                                                    '8' => 'Extra Small (8px)',
                                                    '10' => 'Small (10px)',
                                                    '11' => 'Medium (11px)',
                                                    '12' => 'Large (12px)',
                                                    '14' => 'Extra Large (14px)'
                                                ])
                                                ->default('11'),

                                            Select::make('print_config.grades_table.cells.font_size')
                                                ->label('Cell Font Size')
                                                ->options([
                                                    '8' => 'Extra Small (8px)',
                                                    '9' => 'Small (9px)',
                                                    '10' => 'Medium (10px)',
                                                    '11' => 'Large (11px)',
                                                    '12' => 'Extra Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.grades_table.cells.padding')
                                                ->label('Cell Padding')
                                                ->options([
                                                    '2' => 'Extra Tight (2px)',
                                                    '4' => 'Tight (4px)',
                                                    '6' => 'Normal (6px)',
                                                    '8' => 'Relaxed (8px)',
                                                    '10' => 'Spacious (10px)'
                                                ])
                                                ->default('6'),

                                            Select::make('print_config.grades_table.margin_bottom')
                                                ->label('Table Bottom Margin')
                                                ->options([
                                                    '4' => 'Extra Small (4px)',
                                                    '8' => 'Small (8px)',
                                                    '12' => 'Medium (12px)',
                                                    '16' => 'Large (16px)',
                                                    '20' => 'Extra Large (20px)'
                                                ])
                                                ->default('12'),

                                            Select::make('print_config.grades_table.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '0.8' => 'Extra Compact (0.8)',
                                                    '1' => 'Compact (1)',
                                                    '1.2' => 'Normal (1.2)',
                                                    '1.4' => 'Relaxed (1.4)',
                                                    '1.6' => 'Spacious (1.6)'
                                                ])
                                                ->default('1.2'),

                                            Select::make('print_config.grades_table.container_padding')
                                                ->label('Container Padding')
                                                ->options([
                                                    '4' => 'Extra Tight (4px)',
                                                    '8' => 'Tight (8px)',
                                                    '12' => 'Normal (12px)',
                                                    '16' => 'Relaxed (16px)',
                                                    '20' => 'Spacious (20px)'
                                                ])
                                                ->default('12')
                                        ])
                                        ->columns(2),
                                ])->collapsed(),

                            Section::make('Activities Print Settings')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.activities.section_title.font_size')
                                                ->label('Section Title Font Size')
                                                ->options([
                                                    '10' => 'Small (10px)',
                                                    '12' => 'Medium (12px)',
                                                    '14' => 'Large (14px)',
                                                    '16' => 'Extra Large (16px)'
                                                ])
                                                ->default('12'),

                                            Select::make('print_config.activities.content.font_size')
                                                ->label('Content Font Size')
                                                ->options([
                                                    '8' => 'Small (8px)',
                                                    '10' => 'Medium (10px)',
                                                    '12' => 'Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.activities.row_height')
                                                ->label('Row Height')
                                                ->options([
                                                    '10' => 'Compact (10px)',
                                                    '16' => 'Normal (16px)',
                                                    '20' => 'Relaxed (20px)',
                                                    '24' => 'Spacious (24px)'
                                                ])
                                                ->default('20'),

                                            Select::make('print_config.activities.rating_size')
                                                ->label('Rating Stars Size')
                                                ->options([
                                                    '12' => 'Small (12px)',
                                                    '16' => 'Medium (16px)',
                                                    '20' => 'Large (20px)'
                                                ])
                                                ->default('16'),

                                            Select::make('print_config.activities.table_margin_bottom')
                                                ->label('Table Margin Bottom')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '8' => 'Normal (8px)',
                                                    '12' => 'Relaxed (12px)',
                                                    '16' => 'Spacious (16px)'
                                                ])
                                                ->default('8'),

                                            Select::make('print_config.activities.table_gap')
                                                ->label('Gap Between Tables')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '6' => 'smaller (6px)',
                                                    '8' => 'small (8px)',
                                                    '12' => 'Normal (12px)',
                                                    '16' => 'Relaxed (16px)',
                                                    '24' => 'Spacious (24px)'
                                                ])
                                                ->default('12'),
                                            Select::make('print_config.activities.table_cell_padding')
                                                ->label('Table Cell Padding')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '8' => 'Normal (8px)',
                                                    '12' => 'Relaxed (12px)',
                                                    '16' => 'Spacious (16px)'
                                                ])
                                                ->default('8'),

                                            Select::make('print_config.activities.table_row_spacing')
                                                ->label('Row Spacing')
                                                ->options([
                                                    '4' => 'Compact (4px)',
                                                    '8' => 'Normal (8px)',
                                                    '12' => 'Comfortable (12px)',
                                                    '16' => 'Spacious (16px)'
                                                ])
                                                ->default('8'),

                                            Select::make('print_config.activities.rating_row_height')
                                                ->label('Rating Row Height')
                                                ->options([
                                                    '5' => 'Compact (5px)',
                                                    '10' => 'Compact (10px)',
                                                    '20' => 'Compact (20px)',
                                                    '24' => 'Normal (24px)',
                                                    '28' => 'Relaxed (28px)',
                                                    '32' => 'Spacious (32px)'
                                                ])
                                                ->default('24'),

                                            Select::make('print_config.activities.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '0.8' => 'Extra Compact (0.8)',
                                                    '0.9' => 'Very Compact (0.9)',
                                                    '1' => 'Compact (1)',
                                                    '1.2' => 'Normal (1.2)',
                                                    '1.4' => 'Relaxed (1.4)'
                                                ])
                                                ->default('1.2'),

                                            Select::make('print_config.activities.grading_scale.font_size')
                                                ->label('Grading Scale Font Size')
                                                ->options([
                                                    '6' => 'Smaller (6px)',
                                                    '8' => 'Small (8px)',
                                                    '10' => 'Medium (10px)',
                                                    '12' => 'Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.activities.grading_scale.cell_padding')
                                                ->label('Grading Scale Cell Padding')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '6' => 'Small (6px)',
                                                    '8' => 'Normal (8px)',
                                                    '12' => 'Relaxed (12px)'
                                                ])
                                                ->default('8'),
                                        ])
                                        ->columns(2),
                                ])->collapsed(),
                            Section::make('Comments Print Settings')
                                ->schema([
                                    Grid::make()
                                        ->schema([
                                            Select::make('print_config.comments.title.font_size')
                                                ->label('Section Title Font Size')
                                                ->options([
                                                    '10' => 'Small (10px)',
                                                    '12' => 'Medium (12px)',
                                                    '14' => 'Large (14px)'
                                                ])
                                                ->default('12'),

                                            Select::make('print_config.comments.content.font_size')
                                                ->label('Content Font Size')
                                                ->options([
                                                    '9' => 'Small (9px)',
                                                    '10' => 'Medium (10px)',
                                                    '12' => 'Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.comments.signature.font_size')
                                                ->label('Signature Font Size')
                                                ->options([
                                                    '8' => 'Small (8px)',
                                                    '10' => 'Medium (10px)',
                                                    '12' => 'Large (12px)'
                                                ])
                                                ->default('10'),

                                            Select::make('print_config.comments.line_height')
                                                ->label('Line Height')
                                                ->options([
                                                    '1' => 'Compact (1)',
                                                    '1.2' => 'Normal (1.2)',
                                                    '1.4' => 'Relaxed (1.4)'
                                                ])
                                                ->default('1.2'),

                                            Select::make('print_config.comments.section_spacing')
                                                ->label('Section Spacing')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '8' => 'small (8px)',
                                                    '12' => 'Normal (12px)',
                                                    '16' => 'Relaxed (16px)'
                                                ])
                                                ->default('12'),

                                            Select::make('print_config.comments.section_gap')
                                                ->label('Gap Between Sections')
                                                ->options([
                                                    '4' => 'Tight (4px)',
                                                    '8' => 'small (8px)',
                                                    '12' => 'Normal (12px)',
                                                    '16' => 'Relaxed (16px)'
                                                ])
                                                ->default('12'),
                                        ])
                                        ->columns(2),
                                    Select::make('print_config.comments.container_padding')
                                        ->label('Container Padding')
                                        ->options([
                                            '8' => 'Tight (8px)',
                                            '12' => 'Normal (12px)',
                                            '16' => 'Relaxed (16px)'
                                        ])
                                        ->default('12'),

                                    Select::make('print_config.comments.title_margin')
                                        ->label('Title Margin')
                                        ->options([
                                            '4' => 'Tight (4px)',
                                            '8' => 'Normal (8px)',
                                            '12' => 'Relaxed (12px)'
                                        ])
                                        ->default('8'),

                                    Select::make('print_config.comments.signature_height')
                                        ->label('Signature Image Height')
                                        ->options([
                                            '24' => 'Small (24px)',
                                            '32' => 'Medium (32px)',
                                            '40' => 'Large (40px)'
                                        ])
                                        ->default('32'),
                                ])->collapsed()
                        ]),

                ])
                ->persistTabInQueryString('report-template')
                ->columnSpanFull()
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Report Card Preview')
                    ->modalContent(
                        fn(ReportTemplate $record): View =>
                        view('report-cards.preview', [
                            'template' => $record,
                            'data' => app(TemplatePreviewService::class)->getSampleData()
                        ])
                    )
                    ->modalWidth('7xl'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportTemplates::route('/'),
            'create' => Pages\CreateReportTemplate::route('/create'),
            'edit' => Pages\EditReportTemplate::route('/{record}/edit'),
        ];
    }
}

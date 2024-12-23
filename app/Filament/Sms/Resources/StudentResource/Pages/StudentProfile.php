<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;
use App\Models\Student;
use Faker\Provider\ar_EG\Text;
use Filament\Infolists\Infolist;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Contracts\HasInfolists;
use App\Filament\Sms\Resources\StudentResource;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;

class StudentProfile extends ViewRecord implements HasInfolists
{
    use InteractsWithInfolists;


    protected static string $resource = StudentResource::class;

    protected static string $view = 'filament.sms.resources.student-resource.pages.view-student-profile';

    public $student;


    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        try {
            $this->student = $this->record;
        } catch (\Exception $e) {
            // Handle the exception, e.g., log it or set an error message
            return;
        }
    }

    // Profile tab info organization 
    public function profileInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->student)
            ->schema([
                // Student Details Grid
                Grid::make([
                    'default' => 1,
                    'md' => 2,
                ])
                    ->schema([
                        // Personal Details - Left Column
                        Section::make('Personal Details')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('full_name')
                                        ->color('primary')
                                        ->label('Student Name')
                                        ->weight('bold'),
                                    TextEntry::make('admission.date_of_birth')->label('Date of Birth')
                                        ->color('primary')
                                        ->date('F j, Y'),
                                    Grid::make(4)->schema([
                                        TextEntry::make('admission.gender')->label('Gender')->color('primary'),
                                        TextEntry::make('admission.religion')->label('Religion')->color('primary'),
                                        TextEntry::make('admission.state.name')->color('primary')->color('primary')
                                            ->label('State Origin'),
                                        TextEntry::make('admission.lga.name')->color('primary')
                                            ->label('LGA Origin'),
                                    ]),
                                ])
                            ])->columnSpan(1),

                        // Contact Info - Right Column    
                        Section::make('Contact Information')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Grid::make(2)->schema([
                                    TextEntry::make('admission.phone_number')->label('Phone Number')->color('primary'),
                                    TextEntry::make('admission.email')->label('Email Address')->color('primary'),
                                ]),
                                TextEntry::make('admission.address')->label('Home Address')->color('primary')
                                    ->columnSpanFull()
                            ])->columnSpan(1),
                    ]),

                Section::make('Admission Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('classRoom.name')->label('Class Room')->color('primary'),
                        TextEntry::make('admission.admission_number')->label('Admission Number')->color('primary'),
                        TextEntry::make('admission.admitted_date')->label('Admitted Date')->color('primary'),
                        TextEntry::make('admission.application_date')->label('Application Date')->color('primary'),
                    ])->visible(fn($record) => !empty($record->admission?->admission_number)),
                // Medical Details
                Section::make('Medical Information')
                    ->icon('heroicon-o-heart')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('admission.blood_group')->label('Blood Group')->color('primary'),
                        TextEntry::make('admission.genotype')->label('Genotype')->color('primary'),
                        TextEntry::make('admission.disability_type')->label('Disability Type')->color('primary')
                            ->visible(fn($record) => !empty($record->admission?->disability_type))->color('primary'),
                        TextEntry::make('admission.disability_description')->label('Disability Description')->color('primary')
                            ->visible(fn($record) => !empty($record->admission?->disability_description))
                            ->columnSpanFull()
                    ]),

                // Guardian Info
                Section::make('Parent/Guardian Information')
                    ->description('Primary contact person details')
                    ->icon('heroicon-o-users')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('admission.guardian_name')->label('Guardian Name')->color('primary'),
                        TextEntry::make('admission.guardian_relationship')->label('Relationship')->color('primary'),
                        TextEntry::make('admission.guardian_phone_number')->label('Phone Number')->color('primary'),
                        TextEntry::make('admission.guardian_email')->label('Email Address')->color('primary'),
                        TextEntry::make('admission.guardian_address')->label('Home Address')->color('primary')
                            ->columnSpanFull()
                    ]),

                // Emergency Contact
                Section::make('Emergency Contact')
                    ->icon('heroicon-o-exclamation-circle')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('admission.emergency_contact_name')->label('Contact Name')->color('primary'),
                        TextEntry::make('admission.emergency_contact_relationship')->label('Relationship')->color('primary'),
                        TextEntry::make('admission.emergency_contact_phone_number')->label('Phone Number')->color('primary'),
                        TextEntry::make('admission.emergency_contact_email')->label('Email Address')->color('primary'),
                    ])
            ]);
    }

    public function academicInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->student)
            ->schema([]);
    }
}

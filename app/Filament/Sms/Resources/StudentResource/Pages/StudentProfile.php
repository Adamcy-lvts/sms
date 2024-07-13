<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;
use App\Models\Student;
use Faker\Provider\ar_EG\Text;
use Filament\Infolists\Infolist;
use Filament\Forms\Contracts\HasForms;
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

    public function profileInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->student)
            ->schema([
                Fieldset::make('Student Information')
                    ->schema([
                        TextEntry::make('full_name')->label('Student Name'),
                        TextEntry::make('date_of_birth'),
                        TextEntry::make('admission.gender')->label('Gender'),

                        TextEntry::make('admission.state.name')->label('State of Origin'),
                        TextEntry::make('admission.lga.name')->label('LGA of Origin'),
                        TextEntry::make('admission.phone_number')->label('Phone Number'),
                        TextEntry::make('admission.email')->label('Email Address'),
                        TextEntry::make('admission.address')->label('Address'),
                        TextEntry::make('admission.religion')->label('Religion'),
                        TextEntry::make('admission.blood_group')->label('Blood Group'),
                        TextEntry::make('admission.genotype')->label('Genotype'),

                    ])
                    ->columns(3),
                Fieldset::make('Parent/Guardian Information')
                    ->schema([
                        TextEntry::make('admission.guardian_name')->label('Parent/Guardian Name'),
                        TextEntry::make('admission.guardian_relationship')->label('Relationship'),
                        TextEntry::make('admission.guardian_phone_number')->label('Phone Number'),
                        TextEntry::make('admission.guardian_email')->label('Email Address'),
                        TextEntry::make('admission.guardian_address')->label('Address'),

                    ])
                    ->columns(2),
                Fieldset::make('Emergency Contact Information')
                    ->schema([
                        TextEntry::make('admission.emergency_contact_name')->label('Emergency Contact Name'),
                        TextEntry::make('admission.emergency_contact_relationship')->label('Contact Relationship'),
                        TextEntry::make('admission.emergency_contact_phone_number')->label('Emergency Phone Number'),
                        TextEntry::make('admission.emergency_contact_email')->label('Emergency Email Address'),

                    ])
                    ->columns(2),

                Split::make([
                    Section::make([
                        TextEntry::make('admission.disabilty_type')->label('Disability Type'),

                    ])->grow(false),
                    Section::make([
                        TextEntry::make('admission.disability_description')->label('Disability Description')
                            ->markdown()
                            ->prose()->grow(false),
                    ]),
                ])->from('md')
            ]);
    }

    public function academicInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->student)
            ->schema([
                Fieldset::make('Admission Information')
                    ->schema([
                        TextEntry::make('classRoom.name')->label('Class Room'),
                        TextEntry::make('admission.admission_number')->label('Admission Number'),
                        TextEntry::make('admission.admitted_date')->label('Admitted Date'),
                        TextEntry::make('admission.application_date')->label('Application Date'),
                    ])
            ]);
    }
}

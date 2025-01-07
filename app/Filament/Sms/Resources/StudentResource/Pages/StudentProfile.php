<?php

namespace App\Filament\Sms\Resources\StudentResource\Pages;

use Filament\Actions;
use App\Models\Student;
use App\Models\ReportCard;
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
use Filament\Infolists\Components\RepeatableEntry;
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
                                Grid::make(3)->schema([
                                    TextEntry::make('full_name')
                                        ->color('primary')
                                        ->label('Student Name'),
                                    TextEntry::make('admission.date_of_birth')->label('Date of Birth')
                                        ->color('primary')
                                        ->date('F j, Y'),
                                    TextEntry::make('status.name')
                                        ->label('Student Status')
                                        ->badge()
                                        ->color(fn(string $state): string => match ($state) {
                                            'active' => 'success',
                                            'inactive' => 'danger',
                                            'graduated' => 'info',
                                            default => 'warning',
                                        }),
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

                Section::make('Academic Information')
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

    protected function getScoreColor($score): string
    {
        return match (true) {
            $score >= 70 => 'success',
            $score >= 60 => 'info',
            $score >= 50 => 'warning',
            default => 'danger'
        };
    }

    public function topSubjectsInfolist(Infolist $infolist): Infolist
    {
        // Get all report cards for student
        $reportCards = ReportCard::where('student_id', $this->student->id)
            ->where('status', 'final')
            ->get();

        // Aggregate subject scores across terms
        $subjectAverages = collect();

        foreach ($reportCards as $report) {
            if (!empty($report->subject_scores)) {
                $subjects = collect($report->subject_scores);
                
                $subjects->each(function ($subject) use (&$subjectAverages) {
                    $name = $subject['name'];
                    if (!$subjectAverages->has($name)) {
                        $subjectAverages[$name] = collect();
                    }
                    $subjectAverages[$name]->push($subject['total']);
                });
            }
        }

        // Calculate overall averages and sort
        $topSubjects = $subjectAverages
            ->map(fn($scores, $name) => [
                'name' => $name,
                'average' => $scores->avg(),
                'highest' => $scores->max(),
                'score_count' => $scores->count()
            ])
            ->sortByDesc('average')
            ->values() // Convert to array with numeric indices
            ->take(5);

        return $infolist
            ->record($this->student)
            ->schema([
                Section::make('Academic Performance')
                    ->description('Student academic performance overview')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                        // Only show if we have subjects
                        Section::make('Top Performing Subjects')
                            ->description('Based on average performance across all terms')
                            ->visible(fn() => $topSubjects->isNotEmpty())
                            ->schema([
                                RepeatableEntry::make('subjects')
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Subject')
                                            ->weight('bold'),
                                        TextEntry::make('average')
                                            ->label('Average Score')
                                            ->formatStateUsing(fn($state) => number_format($state, 1) . '%')
                                            ->color(fn($state) => $this->getScoreColor($state)),
                                        TextEntry::make('highest')
                                            ->label('Highest Score')
                                            ->formatStateUsing(fn($state) => number_format($state, 1) . '%'),
                                        TextEntry::make('score_count')
                                            ->label('Terms Assessed')
                                    ])
                                    ->columns(4)
                            ])
                    ])
            ])
            ->state([
                'subjects' => $topSubjects->toArray()
            ]);
    }
}

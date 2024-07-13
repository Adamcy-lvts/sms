<?php

namespace App\Filament\Sms\Resources\StaffResource\Pages;

use Filament\Actions;
use Filament\Tables\Table;
use App\Models\Qualification;
use App\Models\SalaryPayment;
use Filament\Infolists\Infolist;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Infolists\Components\Split;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Sms\Resources\StaffResource;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Tables\Concerns\InteractsWithTable;

class ViewStaff extends ViewRecord implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = StaffResource::class;

    protected static string $view = 'filament.sms.resources.staff-resource.staff-profile';
    
    public $staff;
    public $qualifications;


    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);

        try {
            $this->staff = $this->record;
            $this->qualifications = Qualification::where('staff_id', $this->staff->id)->first();

            // dd($this->qualifications->qualifications[0]);
        } catch (\Exception $e) {
            // Handle the exception, e.g., log it or set an error message
            return;
        }
    }

    public function profileInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->staff)
            ->schema([
                Section::make([
                    Fieldset::make('Personal Information')
                        ->schema([
                            TextEntry::make('first_name')->label('First Name'),
                            TextEntry::make('last_name')->label('Last Name'),
                            TextEntry::make('middle_name')->label('Middle Name'),
                            TextEntry::make('gender')->label('Gender'),
                            TextEntry::make('date_of_birth')->label('Date of Birth'),
                            TextEntry::make('phone_number')->label('Phone Number'),
                            TextEntry::make('email')->label('Email Address'),
                            TextEntry::make('address')->label('Address'),

                        ])
                        ->columns(3),
                ]),
                Section::make([
                    Fieldset::make('Employee Information')
                        ->schema([

                            TextEntry::make('hire_date')->label('Employment Date'),
                            TextEntry::make('status.name')->label('Employment Status')->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'active' => 'success',
                                    'inactive' => 'danger',
                                    'resigned' => 'warning',
                                    'suspended' => 'danger',
                                    'terminated' => 'danger',
                                    'deceased' => 'gray',
                                }),
                            TextEntry::make('salary')->label('Salary')->formatStateUsing(fn ($state) => formatNaira($state)),
                            TextEntry::make('bank.name')->label('Bank Name'),
                            TextEntry::make('account_number')->label('Account Number'),


                        ])
                        ->columns(2),
                ]),

            ]);
    }

    public function qualificationsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state($this->qualifications->qualifications[0])
            ->schema([

                // KeyValueEntry::make('qualifications')->keyLabel('Qualifications'),
                Section::make([
                    TextEntry::make('name')->label('Qualification'),
                    TextEntry::make('institution')->label('Institution'),
                    TextEntry::make('year_obtained')->label('Year Obtained'),
                ])->columns(2),

            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(SalaryPayment::where('staff_id', $this->staff->id))
            ->columns([
              
                TextColumn::make('amount')->formatStateUsing(fn ($state) => formatNaira($state)),
               
                // TextColumn::make('status')->label('Payment Status')
                //     ->badge()
                //     ->color(fn (string $state): string => match ($state) {
                //         'pending' => 'gray',
                //         'paid' => 'success',
                //     }),
                TextColumn::make('payment_date')->dateTime('F j, Y g:i A'),
                TextColumn::make('payment_method')->label('Payment Method'),
                TextColumn::make('period_start')->label('Period Start')->date('F j, Y'),
                TextColumn::make('period_end')->label('Period End')->date('F j, Y'),
                TextColumn::make('academic_year')->label('Academic Year'),
            ])
            ->filters([
                // ...
            ])
            ->actions([
                // Action::make('View Receipt')
                //     ->url(function (SubsPayment $record): string {
                //         $user = Auth::user(); // Get the authenticated user
                //         $school = $user->schools->first(); // Get the first school associated with the user
                //         $subscription = $school->subscriptions->where('status', 'active')->first(); // Get the active subscription

                //         return SubscriptionReceipt::getUrl(['tenant' => $school->slug, 'record' => $record->id]);
                //     })
                //     ->visible(fn (SubsPayment $record): bool => $record->status === 'paid')
                //     ->openUrlInNewTab()
            ])
            ->bulkActions([
                // ...
            ]);
    }
}

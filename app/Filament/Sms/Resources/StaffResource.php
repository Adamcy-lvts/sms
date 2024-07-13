<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use App\Models\Bank;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Status;
use App\Helpers\Options;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\Designation;
use Filament\Support\RawJs;
use Faker\Provider\ar_EG\Text;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Sms\Resources\StaffResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\StaffResource\RelationManagers;

class StaffResource extends Resource
{
    protected static ?string $model = Staff::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // 'school_id',
    // 'user_id',
    // 'designation_id',
    // 'employee_id',
    // 'status_id',
    // 'first_name',
    // 'last_name',
    // 'middle_name',
    // 'gender',
    // 'date_of_birth',
    // 'phone_number',
    // 'email',
    // 'address',
    // 'hire_date',
    // 'employment_status',
    // 'salary',
    // 'bank_name',
    // 'account_number',
    // 'profile_picture',
    // 'qualifications',
    // 'emergency_contact',

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Wizard::make([
                    Wizard\Step::make('Personal Information')
                        ->columns(2)
                        ->schema([

                            Fieldset::make('Personal Information')

                                ->schema([
                                    Forms\Components\TextInput::make('school_id')->hidden(),
                                    Forms\Components\TextInput::make('user_id')->hidden(),
                                    Forms\Components\Select::make('designation_id')->options(Designation::pluck('name', 'id'))->label('Designation')
                                        ->required(),
                                    Forms\Components\TextInput::make('employee_id')->label('Employee ID'),
                                    Forms\Components\TextInput::make('first_name')->label('First Name')->required(),
                                    Forms\Components\TextInput::make('last_name')->label('Last Name')->required(),
                                    Forms\Components\TextInput::make('middle_name')->label('Middle Name'),
                                    Forms\Components\Select::make('gender')->options(Options::gender())->label('Gender')->native(false)
                                        ->required(),
                                    Forms\Components\DatePicker::make('date_of_birth')->label('Date of Birth')->native(false)->required(),
                                    Forms\Components\FileUpload::make('profile_picture')->label('Profile Picture')->disk('public')->directory('staff_profile_pictures')->columnSpan(2),

                                    Toggle::make('is_teacher')->label('If staff is a teacher'),
                                    Toggle::make('create_user')->label('Create user account for this staff')->onIcon('heroicon-m-bolt')
                                        ->offIcon('heroicon-m-user')
                                ]),

                        ]),


                    Wizard\Step::make('Contact Information')
                        ->columns(2)
                        ->schema([

                            Fieldset::make('Contact Information')

                                ->schema([
                                    Forms\Components\TextInput::make('phone_number')->label('Phone Number')->required(),
                                    Forms\Components\TextInput::make('email')->label('Email')->required(),
                                    Forms\Components\DatePicker::make('hire_date')->label('Hire Date')->native(false)->required(),
                                    Forms\Components\Select::make('status_id')->options(Status::where('type', 'staff')->pluck('name', 'id'))->label('Status')
                                        ->required(),
                                    Forms\Components\Textarea::make('address')->label('Address')->required()->columnSpan(2),

                                ]),

                        ]),

                    Wizard\Step::make('Salary Information')
                        ->columns(2)
                        ->schema([

                            Fieldset::make('Salary Information')

                                ->schema([
                                    TextInput::make('salary')->label('Salary')
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->numeric(),
                                    Forms\Components\Select::make('bank_id')->label('Bank Name')->options(Bank::all()->pluck('name', 'id')),
                                    Forms\Components\TextInput::make('account_number')->label('Account Number')->integer(),
                                    Forms\Components\TextInput::make('account_name')->label('Account Name'),
                                    Forms\Components\Textarea::make('emergency_contact')->label('Emergency Contact'),
                                ]),

                        ]),

                    Wizard\Step::make('Qualification Information')
                        ->columns(2)
                        ->schema([
                            Repeater::make('qualifications')
                                ->schema([
                                    Fieldset::make('Qualification Information')
                                        ->schema([
                                            Forms\Components\TextInput::make('name')->label('Qualification Name'),
                                            Forms\Components\TextInput::make('institution')->label('Institution Name'),
                                            Forms\Components\DatePicker::make('year_obtained')->label('Year Obtained')->native(false),
                                            Forms\Components\FileUpload::make('documents')->label('Uploads Documents')->disk('public')->directory('staff_qualification_documents')->columnSpan(2),
                                        ]),
                                ])->columnSpanFull()
                        ])

                ])->columnSpanFull()->skippable()->persistStepInQueryString()->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                >
                    Submit
                </x-filament::button>
            BLADE))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_picture')->label('Profile Picture')->circular(),
                TextColumn::make('employee_id')->label('Employee ID'),
                TextColumn::make('full_name')->label('Full Name'),
                TextColumn::make('phone_number')->label('Phone Number'),
                TextColumn::make('email')->label('Email'),
                TextColumn::make('designation.name')->label('Designation'),
                TextColumn::make('hire_date')->label('Hire Date')->date('d-m-Y'),
                TextColumn::make('status.name')->label('Employment Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'resigned' => 'warning',
                        'suspended' => 'danger',
                        'terminated' => 'danger',
                        'deceased' => 'gray',
                    }),
                TextColumn::make('salary')->label('Salary')->formatStateUsing(fn ($state) => formatNaira($state)),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListStaff::route('/'),
            'create' => Pages\CreateStaff::route('/create'),
            'edit' => Pages\EditStaff::route('/{record}/edit'),
            'view' => Pages\ViewStaff::route('/{record}/staff-profile'),
        ];
    }
}

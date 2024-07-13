<?php

namespace App\Filament\Sms\Resources;

use App\Models\Lga;
use Filament\Forms;
use Filament\Tables;
use App\Models\State;
use App\Models\Status;
use App\Helpers\Gender;
use Filament\Forms\Get;
use App\Helpers\Options;
use Filament\Forms\Form;
use App\Models\Admission;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\AdmissionResource\Pages;
use App\Filament\Sms\Resources\AdmissionResource\Pages\NewStudent;
use App\Filament\Sms\Resources\AdmissionResource\RelationManagers;
use App\Filament\Sms\Resources\StudentResource\Pages\CreateStudent;

class AdmissionResource extends Resource
{
    protected static ?string $model = Admission::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';



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
                                    Forms\Components\Select::make('academic_session_id')->relationship(name: 'academicSession', titleAttribute: 'name')
                                        ->required(),
                                    Forms\Components\TextInput::make('first_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('last_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('middle_name')
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('date_of_birth')->native(false)
                                        ->required(),
                                    Forms\Components\Select::make('gender')->options(Options::gender())
                                        ->required(),

                                    Forms\Components\TextInput::make('phone_number')
                                        ->tel()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\Select::make('state_id')->options(State::all()->pluck('name', 'id')->toArray())->live()->label('State of Origin')
                                        ->required(),
                                    Forms\Components\Select::make('lga_id')
                                        ->options(fn (Get $get): Collection => Lga::query()
                                            ->where('state_id', $get('state_id'))
                                            ->pluck('name', 'id'))->required()->label('Local Government Area'),
                                ]),
                            Forms\Components\FileUpload::make('passport_photograph')->label('Passport Photograph')->disk('public')->directory('admission_passport')->columnSpan(2),

                        ]),

                    Wizard\Step::make('Personal Information 2')
                        ->schema([
                            Fieldset::make('Personal Infomration 2')
                                ->schema([
                                    Forms\Components\Textarea::make('address')
                                        ->required()
                                        ->maxLength(255)->columnSpan(2),
                                    Forms\Components\Select::make('religion')->options(Options::religion()),
                                    Forms\Components\Select::make('blood_group')->options(Options::bloodGroup()),
                                    Forms\Components\Select::make('genotype')->options(Options::genotype()),
                                    Forms\Components\Select::make('type')->options(Options::disability())->live()->label('Disability')
                                        ->afterStateUpdated(fn (Select $component) => $component
                                            ->getContainer()
                                            ->getComponent('dynamicTypeFields')
                                            ->getChildComponentContainer()
                                            ->fill()),

                                    Section::make()
                                        ->schema(fn (Get $get): array => match ($get('type')) {
                                            'Yes' => [
                                                Forms\Components\TextInput::make('disability_type')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\Textarea::make('disability_description')
                                                    ->required()
                                                    ->maxLength(255)->columnSpan(2),
                                            ],
                                            default => [],
                                        })->key('dynamicTypeFields'),

                                    Forms\Components\TextInput::make('previous_school_name')
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('previous_class')
                                        ->maxLength(255),
                                    Forms\Components\DatePicker::make('admitted_date')->native(false)
                                        ->required(),
                                    Forms\Components\DatePicker::make('application_date')->native(false),
                                    Forms\Components\TextInput::make('admission_number')
                                        ->maxLength(255),
                                    Forms\Components\Select::make('status_id')->options(Status::where('type', 'admission')->pluck('name', 'id')->toArray())->label('Status')
                                        ->required(),
                                ])
                        ]),

                    Wizard\Step::make('Guardian/Parent Information')
                        ->schema([
                            Fieldset::make('Guardian/Parent Information')
                                ->schema([
                                    Forms\Components\TextInput::make('guardian_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_relationship')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_phone_number')
                                        ->tel()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('guardian_email')
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('guardian_address')
                                        ->maxLength(255)->columnSpan(2),
                                ]),
                        ]),
                    Wizard\Step::make('Emergency Contact Information')
                        ->schema([
                            Fieldset::make('Personal Infomration 2')
                                ->schema([
                                    Forms\Components\TextInput::make('emergency_contact_name')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_relationship')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_phone_number')
                                        ->tel()
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('emergency_contact_email')
                                        ->email()
                                        ->maxLength(255),
                                ]),

                        ]),
                ])->skippable()->persistStepInQueryString()->submitAction(new HtmlString(Blade::render(<<<BLADE
                <x-filament::button
                    type="submit"
                    size="sm"
                >
                    Submit
                </x-filament::button>
            BLADE))),

            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('passport_photograph')->circular()->label('image')
                    ->height(50),
                Tables\Columns\TextColumn::make('academicSession.name')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('full_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('phone_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state.name')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('lga.name')
                    ->numeric()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('admission_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('admission_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Enroll Student')
                    ->url(function (Admission $record): string {
                        $user = Auth::user(); // Get the authenticated user
                        $school = $user->schools->first(); // Get the first school associated with the user

                        return CreateStudent::getUrl(['tenant' => $school->slug, 'record' => $record]);
                    })
                    ->visible(fn (Admission $record): bool => $record->exists())
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListAdmissions::route('/'),
            'create' => Pages\CreateAdmission::route('/create'),
            'edit' => Pages\EditAdmission::route('/{record}/edit'),
            'view' => Pages\ViewAdmissionLetter::route('/{record}'),
            'newstudent' => Pages\NewStudent::route('{record}/new-student'),

        ];
    }
}

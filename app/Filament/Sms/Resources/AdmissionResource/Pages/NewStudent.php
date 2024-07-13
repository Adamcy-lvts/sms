<?php

namespace App\Filament\Sms\Resources\AdmissionResource\Pages;

use App\Models\Student;
use Filament\Forms\Form;
use App\Models\Admission;
use App\Models\ClassRoom;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Filament\Sms\Resources\AdmissionResource;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class NewStudent extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = AdmissionResource::class;

    protected static string $view = 'filament.sms.resources.admission-resource.pages.new-student';

    public $admissionId;
    public $admission;
    public $first_name;
    public $last_name;
    public $middle_name;
    public $date_of_birth;
    public $class_room;
    public $phone;
    public $address;
    public $admitted_year;
    public $profile_picture;
    public $admission_id;

    public ?array $data = [];



    public function mount($record): void
    {
        // $this->record = $this->resolveRecord($record);

        $this->admission = Admission::findOrFail($record);
        // dd($this->record);

        $this->form->fill([
            'school_id' => $this->admission->school_id,
            'first_name' => $this->admission->first_name,
            'last_name' => $this->admission->last_name,
            'middle_name' => $this->admission->middle_name,
            'date_of_birth' => $this->admission->date_of_birth,
            'class_room_id' => $this->admission->class_room_id,
            'phone' => $this->admission->phone_number,
            'address' => $this->admission->address,
            'admitted_year' => $this->admission->admission_date,
            'addmission_id' => $this->admission->id,
            'profile_picture' => $this->admission->passport_photograph,
        ]);
    }


    public function form(Form $form): Form
    {
        return $form
            ->schema([


                Fieldset::make('Student Information')
                    ->schema([
                        TextInput::make('school_id')->hidden(),
                        FileUpload::make('profile_picture')->label('Profile Picture')->required()->columnSpanFull(),
                        TextInput::make('first_name')->label('First Name')->required(),
                        TextInput::make('last_name')->label('Last Name')->required(),
                        TextInput::make('middle_name')->label('Middle Name'),
                        TextInput::make('date_of_birth')->label('Date of Birth')->required(),
                        TextInput::make('phone')->label('Phone'),
                        Textarea::make('address')->label('Address')->required(),
                        DatePicker::make('admitted_year')->label('Admitted Year')->required(),
                     
                       
                    ])
                    ->columns(2),

                Fieldset::make('Give Student Class Room')
                    ->schema([
                        Select::make('class_room_id')->label('Class Room')->options(ClassRoom::all()->pluck('name', 'id'))->required(),
                    ])
                    ->columns(2)


            ])
            ->statePath('data');
    }

    protected function getFormModel(): string
    {
        return Student::class;
    }

    public function create(): void
    {
        // dd($this->data);

        Student::create([$this->data]);

        Notification::make() 
        ->title('New Student Created Successfully')
        ->success()
        ->send(); 

    redirect()->route('filament.sms.resources.students.index', ['tenant' => $this->admission->school->slug]);
    }
}

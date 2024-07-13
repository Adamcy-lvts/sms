<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use App\Models\Term;
use Filament\Tables;
use App\Models\Status;
use App\Models\Payment;
use App\Models\Student;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\ClassRoom;
use Filament\Tables\Table;
use App\Models\PaymentType;
use Filament\Support\RawJs;
use App\Models\PaymentMethod;
use App\Models\AcademicSession;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Date;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Sms\Resources\PaymentResource\Pages;
use App\Filament\Sms\Resources\PaymentResource\RelationManagers;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Section::make('Search Student')
                    ->description('Search for a student by name, student ID, admission number or phone number.')
                    ->schema([
                        Select::make('student_id')->placeholder('Search for a student by name, student ID, admission number or phone number.')
                            ->label('Student')
                            ->live()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) {
                                return Student::where('first_name', 'like', "%{$search}%")
                                    ->orWhere('last_name', 'like', "%{$search}%")
                                    ->orWhere('identification_number', 'like', "%{$search}%")
                                    ->orWhere('admission_number', 'like', "%{$search}%")
                                    ->orWhere('phone_number', 'like', "%{$search}%")
                                    ->orWhereHas('classRoom', function ($query) use ($search) {
                                        $query->where('name', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($student) {
                                        $label = $student->full_name . ' - ' . $student->student_id_number;
                                        if ($student->admission_number) {
                                            $label .= ' (Adm: ' . $student->admission_number . ')';
                                        } elseif ($student->classRoom) {
                                            $label .= $student->classRoom->name;
                                        } elseif ($student->phone_number) {
                                            $label .= $student->phone_number;
                                        }
                                        return [$student->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $student = Student::find($value);
                                if (!$student) return null;

                                $label = $student->full_name . ' - ' . $student->student_id_number;
                                if ($student->admission_number) {
                                    $label .= ' (Adm: ' . $student->admission_number . ')';
                                }
                                return $label;
                            })
                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                if ($state) {
                                    $student = Student::find($state);
                                    if ($student) {
                                        $set('class_room_id', $student->class_room_id);
                                    }
                                } else {
                                    $set('class_room_id', null);
                                }
                            }),
                    ]),

                Section::make('Payment Details')
                    ->description('Enter payment details.')
                    ->schema([
                        Select::make('academic_session_id')->options(AcademicSession::all()->pluck('name', 'id')->toArray())->live()->label('Academic Session')->native(false),
                        Select::make('term_id')
                            ->options(function (Get $get) {
                                $academicSessionId = $get('academic_session_id');
                                if (!$academicSessionId) {
                                    return [];
                                }
                                return Term::where('academic_session_id', $academicSessionId)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->disabled(fn (Get $get) => !$get('academic_session_id'))
                            ->label('Term'),

                        Select::make('payment_type_id')->options(
                            PaymentType::all()->pluck('name', 'id'),
                        )->label('Payment Type')->live()->native(false),
                        Select::make('class_room_id')
                            ->label('Class Room')
                            ->options(ClassRoom::all()->pluck('name', 'id'))
                            ->native(false)
                            ->disabled(fn (Get $get) => !$get('student_id')),
                        TextInput::make('amount')->label('Amount')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric()
                            ->required(
                                function (Get $get, Set $set) {
                                    $amount = 0;
                                    $paymentTypeId = $get('payment_type_id');
                                    if ($paymentTypeId) {
                                        $paymentType = PaymentType::find($paymentTypeId);
                                    }
                                    if ($paymentTypeId && $paymentType) {
                                        $amount = $set('amount', $paymentType->amount);
                                    }

                                    return $amount;
                                }
                            ),

                        Select::make('status_id')->options(Status::where('type', 'payment')->pluck('name', 'id'))->label('Status')->native(false),
                        Select::make('payment_method_id')->options(PaymentMethod::all()->pluck('name', 'id'))->label('Payment Method')->native(false),
                        DateTimePicker::make('due_date')->label('Due Date')->native(false),
                        DateTimePicker::make('paid_at')->label('Paid At')->native(false),
                        TextInput::make('payer_name')->label('Payer Name'),
                        TextInput::make('payer_phone_number')->label('Phone Number'),
                        TextInput::make('reference'),
                        Textarea::make('remark')->columnSpan(2),
                        Hidden::make('created_by')
                            ->default(auth()->id())
                            ->dehydrated(true)
                            ->required(),

                        Hidden::make('updated_by')
                            ->default(auth()->id())
                            ->dehydrated(true)
                            ->required(),
                    ])->columns(2),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicSession.name'),
                TextColumn::make('term.name'),
                TextColumn::make('student.full_name'),
                TextColumn::make('student.classRoom.name'),
                TextColumn::make('paymentType.name'),
                TextColumn::make('amount')->formatStateUsing(fn ($state) => formatNaira($state)),
                TextColumn::make('status.name')->label('Payment Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'paid' => 'success',
                        'completed' => 'success',
                        'refunded' => 'warning',
                        'unpaid' => 'danger',
                    }),
                TextColumn::make('paymentMethod.name'),
                TextColumn::make('reference')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('description')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('paid_at')->dateTime('d-m-Y H:i:s A')->label('Paid On'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }

    public static function getNairaFormatter(): RawJs
    {
        return RawJs::make(<<<'JS'
        function (number) {
            number = parseFloat(number);
            if (isNaN(number)) return '';
            if (number % 1 === 0) {
                return '₦' + number.toLocaleString('en-US');
            } else {
                return '₦' + number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }
    JS);
    }
}

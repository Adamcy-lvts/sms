<?php

namespace App\Filament\Sms\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Staff;
use App\Models\Expense;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ExpenseItem;
use Illuminate\Support\Str;
use Filament\Facades\Filament;
use App\Models\ExpenseCategory;
use App\Services\StatusService;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Laravel\SerializableClosure\Serializers\Native;
use App\Filament\Sms\Resources\ExpenseResource\Pages;
use App\Filament\Sms\Resources\ExpenseResource\RelationManagers;

class ExpenseResource extends Resource
{
    protected static ?string $model = Expense::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Management';

    public static function form(Form $form): Form
    {
        $tenant = Filament::getTenant() ?? null;

        return $form->schema([
            Grid::make()
                ->schema([
                    // Left Column - Expense Information
                    Section::make('Basic Information')
                        ->columnSpan(['lg' => 1]) // Take up one column on large screens
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    DatePicker::make('expense_date')
                                        ->default(now())
                                        ->native(false)
                                        ->required(),

                                    TextInput::make('reference')
                                        ->default(fn() => 'EXP-' . strtoupper(Str::random(8)))
                                        ->disabled()
                                        ->dehydrated(),

                                    Select::make('status')
                                        ->options([
                                            'pending' => 'Pending',
                                            'approved' => 'Approved',
                                            'paid' => 'Paid'
                                        ])
                                        ->default('pending')
                                        ->required(),
                                ])
                        ]),

                    // Right Column - Payment Details
                    Section::make('Payment Details')
                        ->columnSpan(['lg' => 1]) // Take up one column on large screens
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('payment_method')
                                        ->native(false)
                                        ->options([
                                            'cash' => 'Cash',
                                            'bank_transfer' => 'Bank Transfer',
                                            'cheque' => 'Cheque'
                                        ])
                                        ->required(),

                                    TextInput::make('receipt_number'),

                                    Select::make('academic_session_id')
                                        ->native(false)
                                        ->label('Academic Session')
                                        ->options(function () use ($tenant) {
                                            return $tenant?->academicSessions()
                                                ->pluck('name', 'id')
                                                ->toArray() ?? [];
                                        })
                                        ->default(fn() => config('app.current_session')?->id ?? null)
                                        ->required(),

                                    Select::make('term_id')
                                        ->native(false)
                                        ->label('Term')
                                        ->options(function () use ($tenant) {
                                            return $tenant?->terms()
                                                ->pluck('name', 'id')
                                                ->toArray() ?? [];
                                        })
                                        ->default(fn() => config('app.current_term')?->id ?? null)
                                        ->required(),


                                ]),
                        ]),
                ]), // Use 12-column grid system

            // Summary Card
            Section::make('Summary')->schema([
                Grid::make(3)->schema([
                    TextInput::make('total_items')
                        ->label('Total Items')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),

                    TextInput::make('total_quantity')
                        ->label('Total Quantity')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),

                    TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->prefix('₦')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),
                ]),
            ])->columnSpan(['lg' => 2]),

            // Expense Items Section - Using Repeater
            Section::make('Expense Items')
                ->description('Add one or more items to this expense')
                ->schema([
                    Repeater::make('items')
                        ->schema([
                            Grid::make(2)->schema([
                                Select::make('expense_category_id')
                                    ->label('Category')
                                    ->options(fn() => ExpenseCategory::all()->pluck('name', 'id')->toArray() ?? [])
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set) => $set('expense_item_id', null)),

                                Select::make('expense_item_id')
                                    ->label('Item')
                                    ->options(function (callable $get) use ($tenant) {
                                        $categoryId = $get('expense_category_id');
                                        if (!$categoryId || !$tenant) return [];

                                        return ExpenseItem::where('school_id', $tenant->id)
                                            ->where('expense_category_id', $categoryId)
                                            ->where('is_active', true)
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => sprintf(
                                                    "%s (%s - ₦%s)",
                                                    $item->name ?? '',
                                                    $item->unit ?? 'unit',
                                                    $item->default_amount ?? '0'
                                                )
                                            ])
                                            ->toArray() ?? [];
                                    })
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $item = ExpenseItem::find($state);
                                            $set('unit_price', $item?->default_amount ?? 0);
                                            $set('unit', $item?->unit ?? 'unit');
                                            $set('quantity', 1);
                                            $set('amount', $item?->default_amount ?? 0);
                                        }
                                    })
                                    ->required(),
                            ]),

                            Grid::make(4)->schema([
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $get, Set $set, $livewire) {
                                        $unitPrice = $get('unit_price');
                                        $set('amount', $unitPrice * $state);

                                        // Recalculate totals
                                        $items = collect($livewire->data['items'] ?? []);
                                        $livewire->data['total_items'] = $items->count();
                                        $livewire->data['total_quantity'] = $items->sum('quantity');
                                        $livewire->data['total_amount'] = $items->sum('amount');
                                    })
                                    ->required(),

                                TextInput::make('unit')
                                    ->label('Unit')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('amount')
                                    ->label('Total Amount')
                                    ->numeric()
                                    ->prefix('₦')
                                    ->disabled()
                                    ->dehydrated(),
                            ]),

                            Textarea::make('description')
                                ->label('Item Note')
                                ->rows(2)
                                ->columnSpan('full'),
                        ])
                        ->columns(1)
                        ->itemLabel(function (array $state): ?string {
                            if (isset($state['expense_item_id'])) {
                                $item = ExpenseItem::find($state['expense_item_id']);
                                return $item ? "{$item->name} - ₦{$state['amount']}" : null;
                            }
                            return null;
                        })
                        ->live() // Make repeater live
                        ->afterStateUpdated(function ($state, $livewire) {
                            // Calculate totals
                            $items = collect($state ?? []);
                            // dd($items);
                            $livewire->data['total_items'] = $items->count();
                            $livewire->data['total_quantity'] = $items->sum('quantity');
                            $livewire->data['total_amount'] = $items->sum('amount');
                        })
                        ->collapsible()
                        ->defaultItems(1)
                        ->addActionLabel('Add Another Item')
                        ->reorderableWithButtons()
                        ->cloneable()
                ]),

            // Summary Card
            Section::make('Summary')->schema([
                Grid::make(3)->schema([
                    TextInput::make('total_items')
                        ->label('Total Items')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),

                    TextInput::make('total_quantity')
                        ->label('Total Quantity')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),

                    TextInput::make('total_amount')
                        ->label('Total Amount')
                        ->prefix('₦')
                        ->default(0)
                        ->disabled()
                        ->dehydrated(false)
                        ->reactive(),
                ]),
            ])->columnSpan(['lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $tenant = Filament::getTenant() ?? null;
        return $table
            ->defaultGroup('category.name')
            ->columns([
                TextColumn::make('expense_date')
                    ->date()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(fn ($state) => $state ?? 'Uncategorized'),

                TextColumn::make('category.type')
                    ->label('Expense Type')
                    ->badge()
                    ->color(
                        fn(string $state): string =>
                        match ($state ?? '') {
                            'fixed' => 'info',
                            'variable' => 'warning',
                            default => 'gray'
                        }
                    ),

                TextColumn::make('frequency')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reference')
                    ->searchable()->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('amount')
                    ->money('NGN')
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('status')
                    ->badge()
                    ->color(
                        fn(string $state): string =>
                        match ($state) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            'paid' => 'info',
                            default => 'gray'
                        }
                    ),
            ])
            ->filters([
                SelectFilter::make('academic_session_id')
                    ->label('Academic Session')
                    ->options(function () use ($tenant) {
                        return $tenant?->academicSessions()
                            ->pluck('name', 'id')
                            ->toArray() ?? [];
                    })
                    ->default(fn() => config('app.current_session')?->id ?? null ),

                SelectFilter::make('term_id')
                    ->label('Term')
                    ->options(function () use ($tenant) {
                        return $tenant?->terms()
                            ->pluck('name', 'id')
                            ->toArray() ?? [];
                    })
                    ->default(fn() => config('app.current_term')?->id ?? null ),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'paid' => 'Paid'
                    ]),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
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
            'index' => Pages\ListExpenses::route('/'),
            'create' => Pages\CreateExpense::route('/create'),
            'edit' => Pages\EditExpense::route('/{record}/edit'),
        ];
    }
}

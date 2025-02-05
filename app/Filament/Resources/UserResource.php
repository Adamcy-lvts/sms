<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\Role;
use App\Models\User;
use Filament\Tables;
use App\Models\Status;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Permission;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use App\Filament\Resources\UserResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserResource\RelationManagers;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    // Set navigation icon and group
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 6;

    // Define the form for creating/editing users
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // User Information Section
                Section::make('User Information')
                    ->description('Basic user account information')
                    ->schema([
                        TextInput::make('first_name')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('middle_name')
                            ->maxLength(50),

                        TextInput::make('last_name')
                            ->required()
                            ->maxLength(50),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        // Password field with confirmation
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->confirmed()
                            ->minLength(8),

                        TextInput::make('password_confirmation')
                            ->password()
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->minLength(8)
                            ->visible(fn(string $operation): bool => $operation === 'create'),
                    ])->columns(2),

                // School Assignments Section
                Section::make('School Assignments')
                    ->description('Manage school associations')
                    ->schema([
                        Select::make('schools')
                            ->multiple()
                            ->relationship('schools', 'name')
                            ->preload()
                            ->searchable(),

                        Select::make('status_id')
                            ->options(Status::where('type', 'user')->pluck('name', 'id'))
                            ->default(Status::where('type', 'user')->where('name', 'active')->first()?->id)
                            ->required()
                            ->preload(),
                    ])
            ]);
    }

    // Define the table configuration for listing users
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('first_name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('last_name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('schools.name')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('status.name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        'block' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                // TextColumn::make('roles_count')
                //     ->badge()
                //     ->color('primary')
                //     ->label('Roles')
                //     ->getStateUsing(fn ($record) => $record->roles()->permissons()
                //         ->count() . ' Roles')
                //     ->description(fn ($record) => $record->getRoleNames()->join(', ')),

                // TextColumn::make('permissions_count')
                //     ->badge()
                //     ->color('success')
                //     ->label('Permissions')
                //     ->getStateUsing(function ($record) {
                //         $permissions = $record->roles->permissions(); // Get the relationship
                //         // dd($permissions->get()); // Dump and die with the permissions collection
                //         // Or alternatively:
                //         // dump($permissions->get()); // Dump but continue execution
                //         return $permissions->count() . ' Permissions';
                //     })
                //     ->tooltip(fn ($record) => $record->getAllPermissions()
                //         ->pluck('name')
                //         ->join(', ')),
            ])
            ->filters([
                // Add status filter
                SelectFilter::make('status')
                    ->relationship('status', 'name'),

                // Add school filter
                SelectFilter::make('schools')
                    ->relationship('schools', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->headerActions([
                Action::make('create_role')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Select::make('school_id')
                            ->label('School')
                            ->relationship('schools', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('permissions')
                            ->multiple()
                            ->preload()
                            ->relationship('permissions', 'name')
                            ->searchable(),
                    ])
                    ->action(function (array $data): void {
                        $role = Role::create([
                            'name' => $data['name'],
                            'team_id' => $data['school_id'],
                            'guard_name' => 'web',
                        ]);

                        if (isset($data['permissions'])) {
                            $role->syncPermissions($data['permissions']);
                        }

                        Notification::make()
                            ->success()
                            ->title('Role Created')
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),

                Action::make('manage_roles')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Select::make('school_id')
                            ->label('School')
                            ->relationship('schools', 'name')
                            ->required()
                            ->reactive()
                            ->searchable(),
                        Select::make('roles')
                            ->multiple()
                            ->preload()
                            ->options(function (Get $get) {
                                $schoolId = $get('school_id');
                                if (!$schoolId) return [];
                                
                                return Role::where('team_id', $schoolId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable(),
                    ])
                    ->action(function (User $record, array $data): void {
                        $roles = Role::whereIn('id', $data['roles'])
                            ->where('team_id', $data['school_id'])
                            ->get();
                        
                        $record->syncRoles($roles);
                        
                        Notification::make()
                            ->success()
                            ->title('Roles Updated')
                            ->send();
                    }),

                Action::make('manage_permissions')
                    ->icon('heroicon-o-key')
                    ->form([
                        Select::make('role_id')
                            ->label('Select Role')
                            ->options(function (Model $record) {
                                return $record->roles()->pluck('name', 'id');
                            })
                            ->required()
                            ->reactive(),
                        CheckboxList::make('permissions')
                            ->label('Role Permissions')
                            ->options(function () {
                                return Permission::pluck('name', 'id');
                            })
                            ->default(function (Get $get) {
                                $roleId = $get('role_id');
                                if (!$roleId) return [];
                                
                                return Role::find($roleId)
                                    ?->permissions()
                                    ->pluck('id')
                                    ->toArray() ?? [];
                            })
                            ->columns(2)
                            ->searchable()
                            ->bulkToggleable(),
                    ])
                    ->action(function (array $data): void {
                        $role = Role::findOrFail($data['role_id']);
                        $permissions = Permission::whereIn('id', $data['permissions'])->get();
                        
                        $role->syncPermissions($permissions);
                        
                        Notification::make()
                            ->success()
                            ->title('Role Permissions Updated')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->first_name . ' ' . $record->last_name;
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}

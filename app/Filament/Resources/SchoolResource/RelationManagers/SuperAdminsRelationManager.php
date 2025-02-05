<?php

namespace App\Filament\Resources\SchoolResource\RelationManagers;

use Filament\Forms;
use App\Models\Role;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Permission;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Password;
use Illuminate\Database\Eloquent\Collection;
use Filament\Resources\RelationManagers\RelationManager;

class SuperAdminsRelationManager extends RelationManager
{
    protected static string $relationship = 'members';

    protected static ?string $recordTitleAttribute = 'email';

    protected static ?string $title = 'Super Administrators';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(User::class, 'email', ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrateStateUsing(fn($state) => Hash::make($state))
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->rule(Password::default())
                    ->same('passwordConfirmation'),
                Forms\Components\TextInput::make('passwordConfirmation')
                    ->password()
                    ->label('Confirm Password')
                    ->required(fn(string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->modifyQueryUsing(fn (Builder $query) => 
                $query
                    ->select('users.*')
                    ->join('model_has_roles', function ($join) {
                        $join->on('users.id', '=', 'model_has_roles.model_id')
                            ->where('model_has_roles.model_type', User::class);
                    })
                    ->join('roles', function ($join) {
                        $join->on('model_has_roles.role_id', '=', 'roles.id')
                            ->where('roles.name', 'super_admin')
                            ->where('roles.team_id', $this->getOwnerRecord()->id);
                    })
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('school_user')
                            ->whereColumn('school_user.user_id', 'users.id')
                            ->where('school_user.school_id', $this->getOwnerRecord()->id);
                    })
                    ->distinct()
            )
            ->columns([
                Tables\Columns\TextColumn::make('first_name'),
                Tables\Columns\TextColumn::make('last_name'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('roles')
                    ->badge()
                    ->label('Roles')
                    ->getStateUsing(fn ($record) => Role::where('team_id', $this->getOwnerRecord()->id)
                    ->pluck('name', 'id')),

                Tables\Columns\TextColumn::make('permissions_count')
                    ->badge()
                    ->color('success')
                    ->label('Permissions')
                    ->getStateUsing(
                        // can we get the Role::where('team_id', $this->getOwnerRecord()->id)->pluck('name', 'id')
                     // then let's use roles has permission table which has role_id and permission_id
                        // then we can get the permission_id and count it
                        // we don't need name of the permission, just the count
                        fn ($record) => DB::table('role_has_permissions') 
                            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
                            ->where('roles.team_id', $this->getOwnerRecord()->id)
                            ->whereIn('role_id', $record->roles->pluck('id'))
                            ->distinct()
                            ->count('permission_id')

                ),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function (User $user) {
                        // Ensure user is attached to school
                        $this->getOwnerRecord()->members()->attach($user->id);
                        
                        // Assign super_admin role
                        $role = Role::firstOrCreate(
                            [
                                'name' => 'super_admin',
                                'team_id' => $this->getOwnerRecord()->id
                            ],
                            ['guard_name' => 'web']
                        );
                        
                        $user->assignRole($role);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (User $user) {
                        // Remove super_admin role for this school
                        $role = Role::where('name', 'super_admin')
                            ->where('team_id', $this->getOwnerRecord()->id)
                            ->first();
                            
                        if ($role) {
                            $user->removeRole($role);
                        }
                        
                        // Detach from school if no other roles
                        if (!$user->roles()->where('team_id', $this->getOwnerRecord()->id)->exists()) {
                            $this->getOwnerRecord()->members()->detach($user->id);
                        }
                    }),
                Action::make('manage_permissions')
                    ->icon('heroicon-o-key')
                    ->modalHeading('Manage Role Permissions')
                    ->form([
                        Forms\Components\Select::make('role_id')
                            ->label('Select Role to Manage Permissions')
                            ->options(function () {
                                return Role::where('team_id', $this->getOwnerRecord()->id)
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->live(),
                            
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Role Permissions')
                            ->columns(2)
                            ->gridDirection('row')
                            ->bulkToggleable()
                            ->searchable()
                            ->options(function () {
                                return Permission::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->default(function (Get $get) {
                                $roleId = $get('role_id');
                                if (!$roleId) return [];
                                
                                return Role::find($roleId)
                                    ?->permissions()
                                    ->pluck('id')
                                    ->toArray() ?? [];
                            })
                    ])
                    ->action(function (array $data): void {
                        $role = Role::findOrFail($data['role_id']);
                        $permissions = Permission::whereIn('id', $data['permissions'])->get();
                        
                      

                        $role->givePermissionTo($permissions);
                        
                        Notification::make()
                            ->success()
                            ->title('Role permissions updated')
                            ->send();
                    }),

                Action::make('add_role')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Forms\Components\Select::make('roles')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->options(function () {
                                return Role::where('team_id', $this->getOwnerRecord()->id)
                                    ->pluck('name', 'id');
                            })
                            ->default(function ($record) {
                                return $record->roles()
                                    ->where('roles.team_id', $this->getOwnerRecord()->id)
                                    ->pluck('id');
                            })
                    ])
                    ->action(function (User $record, array $data): void {
                        $roles = Role::whereIn('id', $data['roles'])
                            ->where('team_id', $this->getOwnerRecord()->id)
                            ->get();
                            
                        $record->syncRoles($roles);
                        
                        Notification::make()
                            ->success()
                            ->title('Roles updated')
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records) {
                            $role = Role::where('name', 'super_admin')
                                ->where('team_id', $this->getOwnerRecord()->id)
                                ->first();

                            if ($role) {
                                foreach ($records as $user) {
                                    $user->removeRole($role);
                                }
                            }
                        }),
                ]),
            ]);
    }
}

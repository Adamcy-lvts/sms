<?php

namespace App\Filament\Resources\SchoolResource\Pages;

use App\Models\User;
use Filament\Actions;
use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\SchoolResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSchool extends CreateRecord
{
    protected static string $resource = SchoolResource::class;


    protected function handleRecordCreation(array $data): Model
    {
        // dd($data);
        DB::beginTransaction();
        try {
            // Create User model for authentication details
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'password' => $data['password'], // Ensure the password is hashed
            ]);


            // Create Agent record linked to the user
            $school = School::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'slug' =>  Str::slug($data['name']),
                'address' => $data['address'],
                'phone' => $data['phone'],
                'logo' => $data['logo'],
                'settings' => $data['settings']
            ]);

            $school->members()->attach($user->id);

            // Commit transaction if all good
            DB::commit();

            return $school;
        } catch (\Exception $e) {
            // Rollback if there is an error
            DB::rollback();
            Log::error($e->getMessage());
            throw $e;
        }
    }
}

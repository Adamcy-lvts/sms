<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Models\User;
use App\Models\Agent;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Resources\AgentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;

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
                'user_type' => 'agent' // Assuming there's a user_type field to distinguish users
            ]);

            // Assuming you have a role setup for agents, assign it
            // $user->assignRole('agent');

            // Create Agent record linked to the user
            $agent = Agent::create([
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'business_name' => $data['business_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'bank_id' => $data['bank_id'],
                'percentage' => $data['percentage'],
                'fixed_rate' => $data['fixed_rate']
            ]);

            // Commit transaction if all good
            DB::commit();

            return $agent;
        } catch (\Exception $e) {
            // Rollback if there is an error
            DB::rollback();
            Log::error($e->getMessage());
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Models\Agent;
use Filament\Actions;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\AgentResource;
use Unicodeveloper\Paystack\Facades\Paystack;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    public function mount($record): void
    {
        // This will load the agent and its user relationship
        $this->record = Agent::findOrFail($record);

        // Fill form with data from the database
        $this->form->fill([
            'first_name' => $this->record->user->first_name,
            'last_name' => $this->record->user->last_name,
            'email' => $this->record->user->email,
            'status_id' => $this->record->user->status_id,
            'phone' => $this->record->user->phone,
            // Agent fields
            'business_name' => $this->record->business_name,
            'account_number' => $this->record->account_number,
            'account_name' => $this->record->account_name,
            'bank_id' => $this->record->bank_id,
            'percentage' => $this->record->percentage,
            'fixed_rate' => $this->record->fixed_rate,
            'referral_code' => $this->record->referral_code,
            'subaccount_code' => $this->record->subaccount_code,
        ]);
    }


    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Start a database transaction
        DB::beginTransaction();

        try {
            // Update user details linked to the agent
            $record->user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status_id' => $data['status_id'],
                // Include any other user fields that might be in your form
            ]);

            // Update the agent record
            $record->update([
                'business_name' => $data['business_name'],
                'account_number' => $data['account_number'],
                'account_name' => $data['account_name'],
                'bank_id' => $data['bank_id'],
                'percentage' => $data['percentage'],
                'fixed_rate' => $data['fixed_rate'],
                // Include any other fields that might be in your form
            ]);

            // If the subaccount code has changed and is not empty, update it on Paystack
            if (!empty($data['subaccount_code']) && $record->subaccount_code !== $data['subaccount_code']) {
                $updateResult = Paystack::updateSubAccount([
                    'subaccount_code' => $record->subaccount_code, // Existing subaccount code
                    'business_name' => $data['business_name'],
                    'settlement_bank' => $data['bank_id'], // Ensure you have the correct bank code
                    'account_number' => $data['account_number'],
                    'percentage_charge' => $data['percentage'],
                ]);

                // Check the success of the update
                if (!$updateResult['status']) {
                    throw new \Exception('Failed to update subaccount on Paystack: ' . $updateResult['message']);
                }
            }

            // Commit the transaction
            DB::commit();

            return $record;
        } catch (\Exception $e) {
            // Rollback the transaction in case of errors
            DB::rollback();
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

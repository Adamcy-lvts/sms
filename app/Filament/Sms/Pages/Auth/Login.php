<?php

namespace App\Filament\Sms\Pages\Auth;

use App\Models\User;
use Filament\Forms\Form;
use Filament\Facades\Filament;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Blade;
use Filament\Forms\Components\Checkbox;
use Illuminate\Support\Facades\Session;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Livewire\DatabaseNotifications;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Login extends BaseLogin
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.auth.login';


    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }
        Log::info('Authenticating user');
        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();
        Log::info($user);
        // Check user status before proceeding
        if (!$this->checkUserStatus($user)) {
            Filament::auth()->logout();
            return null;
        }

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();



        // Send login notification
        $this->sendLoginNotification($user);
        Log::info('User logged in');
        // Update last login timestamp
        $user->update([
            'last_login_at' => now(),
        ]);
        return app(LoginResponse::class);
    }

    protected function sendLoginNotification(User $user): void
    {
        $tenant = $user->schools->first();

        Log::info($tenant);
        DatabaseNotifications::pollingInterval('15s');

        $notification = Notification::make()
            ->title('User Logged In')
            ->body("{$user->staff?->full_name} has logged in to the system.")
            ->icon('heroicon-o-user')
            ->success();
        Log::info($notification);
        // Get super admins for current tenant
        $superAdmins = Role::query()
            ->where('name', 'super_admin')
            ->whereHas('users', function ($query) use ($tenant) {
                $query->whereHas('schools', function ($q) use ($tenant) {
                    $q->where('schools.id', $tenant->id);
                });
            })
            ->first()
            ?->users ?? collect();
        Log::info($superAdmins);
        // Send notification to each super admin
        foreach ($superAdmins as $admin) {
            $notification->sendToDatabase($admin);
        }
    }

    protected function checkUserStatus(User $user): bool
    {
        if (!$user->status_id) {
            $this->sendStatusNotification('User status not set.');
            return false;
        }

        $status = $user->status;

        if (!$status) {
            $this->sendStatusNotification('Invalid user status.');
            return false;
        }

        $allowedStatuses = ['active'];

        if (!in_array($status->name, $allowedStatuses)) {
            $this->sendStatusNotification("Your account is {$status->name}. Please contact support.");
            return false;
        }

        return true;
    }

    protected function sendStatusNotification(string $message): void
    {
        Notification::make()
            ->title('Access Denied')
            ->body($message)
            ->danger()
            ->persistent()
            ->send();
    }
}

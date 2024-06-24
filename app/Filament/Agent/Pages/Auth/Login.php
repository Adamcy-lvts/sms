<?php

namespace App\Filament\Agent\Pages\Auth;

use Filament\Pages\Auth\Login as AuthLogin;
use Filament\Pages\Page;

class Login extends AuthLogin
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.agent.pages.auth.login';

    
}

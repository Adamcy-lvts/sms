<?php

namespace App\Filament\Sms\Pages\Tenancy;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;

class EditSchoolProfile extends EditTenantProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.sms.pages.tenancy.edit-school-profile';


    public static function getLabel(): string
    {
        return 'School profile';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('email'),
                TextInput::make('address'),
                TextInput::make('phone'),
                FileUpload::make('logo')
                    ->directory('school-logos'),
                // ...
            ]);
    }
}

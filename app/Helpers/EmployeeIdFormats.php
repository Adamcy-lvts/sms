<?php

namespace App\Helpers;  // Change from App\Support to App\Helpers to match your directory structure

class EmployeeIdFormats
{
    public const BASIC = 'basic';
    public const WITH_YEAR = 'with_year';
    public const WITH_DEPARTMENT = 'with_department';
    public const FULL_DATE = 'full_date';
    public const DEPARTMENT_FULL = 'department_full';

    public static function getPresets(): array
    {
        return [
            self::BASIC => [
                'label' => 'Basic (EMP00001)',
                'format' => self::BASIC,
                'example' => 'EMP00001'
            ],
            self::WITH_YEAR => [
                'label' => 'With Year (EMP23001)',
                'format' => self::WITH_YEAR,
                'example' => 'EMP23001'
            ],
            self::WITH_DEPARTMENT => [
                'label' => 'With Department (TCH23001)',
                'format' => self::WITH_DEPARTMENT,
                'example' => 'TCH23001'
            ],
            self::FULL_DATE => [
                'label' => 'Full Date (EMP20231001)',
                'format' => self::FULL_DATE,
                'example' => 'EMP20231001'
            ],
            self::DEPARTMENT_FULL => [
                'label' => 'Department Full (EMPTCH23001)',
                'format' => self::DEPARTMENT_FULL,
                'example' => 'EMPTCH23001'
            ],
        ];
    }
}
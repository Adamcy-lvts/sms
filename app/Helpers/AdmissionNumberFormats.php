<?php

namespace App\Helpers;

class AdmissionNumberFormats 
{
    public static function getPresets(): array 
    {
        return [
            'basic' => [
                'label' => 'Basic (ADM-0001)',
                'format' => '{PREFIX}{SEP:1}{NUM}',
                'example' => 'ADM-0001'
            ],
            'with_year' => [
                'label' => 'With Year (ADM-23-001)',
                'format' => '{PREFIX}{SEP:1}{YY}{SEP:2}{NUM}',
                'example' => 'ADM-23-001'
            ],
            'school_initials' => [
                'label' => 'School Initials (KPS-001)',
                'format' => '{SCHOOL}{SEP:1}{NUM}',
                'example' => 'KPS-001'
            ],
            'school_year' => [
                'label' => 'School with Year (KPS-23-001)',
                'format' => '{SCHOOL}{SEP:1}{YY}{SEP:2}{NUM}',
                'example' => 'KPS-23-001'
            ],
            'with_session' => [
                'label' => 'With Session (ADM-2324-001)',
                'format' => '{PREFIX}{SEP:1}{SESSION}{SEP:2}{NUM}',
                'example' => 'ADM-2324-001'
            ],
            'school_session' => [
                'label' => 'School with Session (KPS-2324-001)',
                'format' => '{SCHOOL}{SEP:1}{SESSION}{SEP:2}{NUM}',
                'example' => 'KPS-2324-001'
            ],
            'custom' => [
                'label' => 'Custom Format',
                'format' => 'custom',
                'example' => 'Custom'
            ]
        ];
    }

    public static function getAvailableTokens(): array
    {
        return [
            '{PREFIX}' => 'Custom prefix (e.g., ADM)',
            '{SCHOOL}' => 'School initials (e.g., KPS)',
            '{YY}' => 'Current year, 2 digits (e.g., 23)',
            '{YYYY}' => 'Current year, 4 digits (e.g., 2023)',
            '{SESSION}' => 'Academic session (e.g., 2324)',
            '{NUM}' => 'Sequential number',
            '{SEP:1}' => 'First separator position',
            '{SEP:2}' => 'Second separator position',
            '{SEP:3}' => 'Third separator position'
        ];
    }
}
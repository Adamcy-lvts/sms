<?php

namespace App\Helpers;

class Options
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }


    const MALE = 'male';
    const FEMALE = 'female';
    


    public static function gender()
    {
        return [
            self::MALE => 'Male',
            self::FEMALE => 'Female',
         
        ];
    }

    // Example method for another set of constants
    public static function bloodGroup() {
        return [
            'A' => 'A',
            'B' => 'B',
            'AB' => 'AB',
            'O' => 'O',
        ];
    }

    public static function genotype() {
        return [
            'AA' => 'AA',
            'AS' => 'AS',
            'SS' => 'SS',
            'AC' => 'AC',
            'SC' => 'SC',
            'CC' => 'CC',
        ];
    }

    public static function religion() {
        return [
            'Islam' => 'Islam',
            'Christianity' => 'Christianity',
            'Others' => 'Others',
        ];
    }

   public static function disability() {
        return [
            'Yes' => 'Yes',
            'No' => 'No',
        ];
    }

    public static function relationship() {
        return [
            'Father' => 'Father',
            'Mother' => 'Mother',
            'Guardian' => 'Guardian',
            'Others' => 'Others',
        ];
    }

    public static function admissionStatus() {
        return [
            'Accepted' => 'Accepted',
            'Pending' => 'Pending',
            'Rejected' => 'Rejected',
        ];
    }
}

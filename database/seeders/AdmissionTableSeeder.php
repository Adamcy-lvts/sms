<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\Admission;
use App\Models\AcademicSession;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdmissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $academicSession = AcademicSession::find(1);

        Admission::create([
            'school_id' => 1,
            'academic_session_id' => 1,
            'session' => $academicSession->name,
            'first_name' => 'Ahmad',
            // 'full_name' => 'Ahmad Salisu Abdullahi',
            'last_name' => 'Salisu',
            'middle_name' => 'Abdullahi',
            'date_of_birth' => Carbon::createFromDate(2000, 6, 31),
            'gender' => 'Male',
            'address' => 'Lagos Street, NUJ Maiduguri',
            'phone_number' => '08012345678',
            'email' => 'ahmad@mail.com',
            'state_id' => 8,
            'lga_id' => 158,
            'religion' => 'Islam',
            'blood_group' => 'A+',
            'genotype' => 'AA',
            'previous_school_name' => 'Government Secondary School, Maiduguri',
            'previous_class' => 'SS3',
            'application_date' => Carbon::createFromDate(2023, 1, 1),
            'admitted_date' => Carbon::createFromDate(2023, 1, 15),
            'admission_number' => 'KIA/23/24/GR1/001.',
            'status_id' => 12,
            'guardian_name' => 'Hassan Mustapha',
            'guardian_relationship' => 'Uncle',
            'guardian_phone_number' => '09032112321',
            'guardian_email' => 'hassan@mail.com',
            'guardian_address' => 'Gwange II Layin Makaranta',
            'emergency_contact_name' => 'Hassan Mustapha',
            'emergency_contact_relationship' => 'Uncle',
            'emergency_contact_phone_number' => '0804532121',
            'emergency_contact_email' => 'hassan@mail.com',
            'disability_type' => null,
            'disability_description' => null,
            'passport_photograph' => 'path/to/photo.jpg',
        ]);

        
    }
}

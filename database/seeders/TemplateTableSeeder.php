<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TemplateTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {



        $templates = [
            [
                'name' => 'Admission Letter Template',
                'slug' => 'admission-letter-template',
                'school_id' => 1,
                'content' => '        <p>&nbsp;</p>
<p><img style="display: block; margin-left: auto; margin-right: auto;" src="/storage/TBfSglvXRAdO8pKWMwY9jlTuxrpyiz3pKfijMRZl.png" alt="" width="100" height="127"></p>
<p style="text-align: center;"><span style="font-size: 25px;"><strong>Khalil Integrated Academy</strong></span></p>
<p style="text-align: center;"><strong>B4/23 House of Assembly Qtrs, Pompomari, Maiduguri</strong></p>
<p style="text-align: center;">&nbsp;</p>
<hr>
<p style="text-align: right;"><strong>date</strong></p>
<p><br><strong>full_name</strong></p>
<p><strong>address</strong></p>
<p>&nbsp;</p>
<p style="text-align: center;"><span style="font-size: 18px;"><strong>OFFER OF PROVISIONAL ADMISSION INTO NURSERY</strong></span><br><span style="font-size: 18px;"><strong>FOR session_name ACADEMIC SESSION</strong></span></p>
<p>&nbsp;</p>
<p>Dear Ahmed Tariq,</p>
<p>I am pleased to inform you that you been offered a provisional admission into Nursery One (1) of Khalil Integrated Academy. Your admission number is <strong>admission_number</strong>.</p>
<p>Your admission is based on your performance in the entrance examination/interview held on Saturday 23rd July, 2022. You are kindly advised to register immediately. Please note that failure to register within the stipulated period would lead to the cancellation of your admission and re-issuance to another person. Resumption date for session_name Academic Session is 1st September, 2023 . Registration details, enrolment policies and guidelines are attached herewith for your guidance and endorsement. Please do not hesitate to reach out to us should you require clarity on anything. Please accept my congratulations.</p>
<p>Sincerely,</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>Aliyu Mohammed</p>
<p>On behalf of Management</p>
<p>Khalil Integrated Academy</p>
<div class="mt-6">
<div class="text-lg mt-5 text-gray-600">
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
</div>
</div>',

            ],
        ];

        if (Template::count() > 0) {
            return;
        }

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}

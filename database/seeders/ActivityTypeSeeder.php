<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\ActivityType;
use Illuminate\Database\Seeder;

class ActivityTypeSeeder extends Seeder
{
    public function run(): void
    {
        $activities = [
            'Sports & Athletics' => [
                ['name' => 'Football', 'code' => 'FB', 'description' => 'Football/Soccer participation and performance', 'icon' => 'futbol', 'color' => '#15803d'],
                ['name' => 'Basketball', 'code' => 'BB', 'description' => 'Basketball team participation', 'icon' => 'basketball', 'color' => '#b91c1c'],
                ['name' => 'Track & Field', 'code' => 'TF', 'description' => 'Athletics and track events', 'icon' => 'running', 'color' => '#0369a1'],
                ['name' => 'Swimming', 'code' => 'SW', 'description' => 'Swimming and water sports', 'icon' => 'person-swimming', 'color' => '#0891b2'],
                ['name' => 'Volleyball', 'code' => 'VB', 'description' => 'Volleyball team participation', 'icon' => 'volleyball', 'color' => '#ea580c'],
                ['name' => 'Table Tennis', 'code' => 'TT', 'description' => 'Table tennis participation', 'icon' => 'table-tennis-paddle-ball', 'color' => '#4f46e5'],
                ['name' => 'Badminton', 'code' => 'BM', 'description' => 'Badminton team activities', 'icon' => 'shuttlecock', 'color' => '#7c3aed'],
                ['name' => 'Cricket', 'code' => 'CK', 'description' => 'Cricket team participation', 'icon' => 'baseball-bat-ball', 'color' => '#2563eb'],
                ['name' => 'Hockey', 'code' => 'HK', 'description' => 'Hockey team participation', 'icon' => 'hockey-puck', 'color' => '#dc2626']
            ],
            'Arts & Culture' => [
                ['name' => 'Drama Club', 'code' => 'DR', 'description' => 'Theater and dramatic performances', 'icon' => 'masks-theater', 'color' => '#7c2d12'],
                ['name' => 'Art Club', 'code' => 'ART', 'description' => 'Visual arts and creative activities', 'icon' => 'palette', 'color' => '#be185d'],
                ['name' => 'Music Band', 'code' => 'MB', 'description' => 'School band participation', 'icon' => 'music', 'color' => '#6d28d9'],
                ['name' => 'Choir', 'code' => 'CH', 'description' => 'School choir participation', 'icon' => 'microphone', 'color' => '#4f46e5'],
                ['name' => 'Dance Club', 'code' => 'DC', 'description' => 'Dance and choreography', 'icon' => 'person-dancing', 'color' => '#db2777'],
                ['name' => 'Photography Club', 'code' => 'PH', 'description' => 'Photography and visual storytelling', 'icon' => 'camera', 'color' => '#0d9488'],
                ['name' => 'Creative Writing', 'code' => 'CW', 'description' => 'Creative writing and literature', 'icon' => 'pen-fancy', 'color' => '#0369a1'],
                ['name' => 'Cultural Dance', 'code' => 'CD', 'description' => 'Traditional and cultural dance', 'icon' => 'person-dancing', 'color' => '#ca8a04'],
                ['name' => 'Film Making', 'code' => 'FM', 'description' => 'Video production and editing', 'icon' => 'film', 'color' => '#be123c']
            ],
            'Academic Clubs' => [
                ['name' => 'Science Club', 'code' => 'SC', 'description' => 'Science experiments and research', 'icon' => 'flask', 'color' => '#059669'],
                ['name' => 'Mathematics Club', 'code' => 'MC', 'description' => 'Advanced mathematics and problem solving', 'icon' => 'calculator', 'color' => '#0891b2'],
                ['name' => 'Debate Club', 'code' => 'DB', 'description' => 'Debating and public speaking', 'icon' => 'comments', 'color' => '#4338ca'],
                ['name' => 'Robotics Club', 'code' => 'RC', 'description' => 'Robotics and programming', 'icon' => 'robot', 'color' => '#7c3aed'],
                ['name' => 'Computer Club', 'code' => 'CC', 'description' => 'Computer programming and technology', 'icon' => 'computer', 'color' => '#0284c7'],
                ['name' => 'Book Club', 'code' => 'BC', 'description' => 'Reading and literature discussion', 'icon' => 'book-open', 'color' => '#9333ea'],
                ['name' => 'Language Club', 'code' => 'LC', 'description' => 'Foreign language learning', 'icon' => 'language', 'color' => '#2563eb'],
                ['name' => 'Chess Club', 'code' => 'CHS', 'description' => 'Chess strategy and tournaments', 'icon' => 'chess', 'color' => '#4b5563'],
                ['name' => 'Electronics Club', 'code' => 'EC', 'description' => 'Electronics and circuitry', 'icon' => 'microchip', 'color' => '#b91c1c']
            ],
            'Leadership & Service' => [
                ['name' => 'Student Council', 'code' => 'SC', 'description' => 'Student leadership and governance', 'icon' => 'users', 'color' => '#1d4ed8'],
                ['name' => 'Community Service', 'code' => 'CS', 'description' => 'Community outreach activities', 'icon' => 'handshake', 'color' => '#166534'],
                ['name' => 'Environmental Club', 'code' => 'EC', 'description' => 'Environmental conservation', 'icon' => 'leaf', 'color' => '#15803d'],
                ['name' => 'Peer Mentoring', 'code' => 'PM', 'description' => 'Peer support and mentoring', 'icon' => 'user-group', 'color' => '#0f766e'],
                ['name' => 'Red Cross Society', 'code' => 'RCS', 'description' => 'First aid and humanitarian services', 'icon' => 'kit-medical', 'color' => '#dc2626'],
                ['name' => 'School Newsletter', 'code' => 'NL', 'description' => 'School publication and journalism', 'icon' => 'newspaper', 'color' => '#0369a1'],
                ['name' => 'Career Club', 'code' => 'CC', 'description' => 'Career guidance and development', 'icon' => 'briefcase', 'color' => '#6d28d9'],
                ['name' => 'Library Assistants', 'code' => 'LA', 'description' => 'Library management and assistance', 'icon' => 'book', 'color' => '#a21caf']
            ]
        ];

        // Default Activities (10 most important)
        $defaultActivityCodes = [
            'FB',  // Football
            'BB',  // Basketball
            'DR',  // Drama Club
            'CH',  // Choir
            'SC',  // Science Club
            'DB',  // Debate Club
            'CHS', // Chess Club
            'STD', // Student Council
            'CS'   // Community Service
        ];

        // Get all schools
        $schools = School::all();

        // In the seeder:
        foreach ($schools as $school) {
            $order = 0;
            foreach ($activities as $category => $categoryActivities) {
                foreach ($categoryActivities as $activity) {
                    ActivityType::create([
                        'school_id' => $school->id,
                        'name' => $activity['name'],
                        'code' => $activity['code'],
                        'description' => $activity['description'],
                        'category' => $category,
                        'icon' => $activity['icon'],
                        'color' => $activity['color'],
                        'display_order' => $order++,
                        'is_default' => in_array($activity['code'], $defaultActivityCodes)
                    ]);
                }
            }
        }
    }
}

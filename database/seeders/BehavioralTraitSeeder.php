<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\BehavioralTrait;
use Illuminate\Database\Seeder;

class BehavioralTraitSeeder extends Seeder
{
    public function run(): void
    {
        $traits = [
            'Learning Skills' => [
                ['name' => 'Attentiveness', 'code' => 'ATT', 'description' => 'Ability to focus and pay attention in class', 'weight' => 1.0],
                ['name' => 'Class Participation', 'code' => 'CP', 'description' => 'Level of active participation in class', 'weight' => 1.0],
                ['name' => 'Homework Completion', 'code' => 'HW', 'description' => 'Consistency in completing homework', 'weight' => 1.0],
                ['name' => 'Organization', 'code' => 'ORG', 'description' => 'Ability to organize work and materials', 'weight' => 1.0],
                ['name' => 'Study Skills', 'code' => 'SS', 'description' => 'Effective study habits and techniques', 'weight' => 1.0],
                ['name' => 'Critical Thinking', 'code' => 'CT', 'description' => 'Ability to analyze and solve problems', 'weight' => 1.0],
                ['name' => 'Research Skills', 'code' => 'RS', 'description' => 'Ability to gather and analyze information', 'weight' => 1.0],
                ['name' => 'Note Taking', 'code' => 'NT', 'description' => 'Ability to take and organize notes effectively', 'weight' => 1.0]
            ],
            'Social Skills' => [
                ['name' => 'Cooperation', 'code' => 'COOP', 'description' => 'Works well with others', 'weight' => 1.0],
                ['name' => 'Respect', 'code' => 'RESP', 'description' => 'Shows respect for teachers and peers', 'weight' => 1.0],
                ['name' => 'Communication', 'code' => 'COMM', 'description' => 'Communicates effectively', 'weight' => 1.0],
                ['name' => 'Team Work', 'code' => 'TW', 'description' => 'Works effectively in groups', 'weight' => 1.0],
                ['name' => 'Leadership', 'code' => 'LEAD', 'description' => 'Shows leadership qualities', 'weight' => 1.0],
                ['name' => 'Conflict Resolution', 'code' => 'CR', 'description' => 'Handles conflicts appropriately', 'weight' => 1.0],
                ['name' => 'Empathy', 'code' => 'EMP', 'description' => 'Shows understanding and care for others', 'weight' => 1.0],
                ['name' => 'Cultural Awareness', 'code' => 'CA', 'description' => 'Respects cultural differences', 'weight' => 1.0]
            ],
            'Personal Development' => [
                ['name' => 'Punctuality', 'code' => 'PUNC', 'description' => 'Arrives to class on time', 'weight' => 1.0],
                ['name' => 'Neatness', 'code' => 'NEAT', 'description' => 'Maintains neat appearance and work', 'weight' => 1.0],
                ['name' => 'Self-Control', 'code' => 'SC', 'description' => 'Shows appropriate self-control', 'weight' => 1.0],
                ['name' => 'Initiative', 'code' => 'INIT', 'description' => 'Shows initiative in learning', 'weight' => 1.0],
                ['name' => 'Self-Confidence', 'code' => 'CONF', 'description' => 'Displays self-confidence', 'weight' => 1.0],
                ['name' => 'Resilience', 'code' => 'RES', 'description' => 'Bounces back from setbacks', 'weight' => 1.0],
                ['name' => 'Goal Setting', 'code' => 'GS', 'description' => 'Sets and works towards goals', 'weight' => 1.0],
                ['name' => 'Personal Hygiene', 'code' => 'PH', 'description' => 'Maintains good personal hygiene', 'weight' => 1.0],
                ['name' => 'Integrity', 'code' => 'INT', 'description' => 'Demonstrates honesty and ethical behavior', 'weight' => 1.0],
            ],
            'Work Habits' => [
                ['name' => 'Time Management', 'code' => 'TM', 'description' => 'Uses time effectively', 'weight' => 1.0],
                ['name' => 'Task Completion', 'code' => 'TC', 'description' => 'Completes tasks on time', 'weight' => 1.0],
                ['name' => 'Effort', 'code' => 'EFF', 'description' => 'Shows consistent effort', 'weight' => 1.0],
                ['name' => 'Independence', 'code' => 'IND', 'description' => 'Works independently', 'weight' => 1.0],
                ['name' => 'Following Instructions', 'code' => 'FI', 'description' => 'Follows directions accurately', 'weight' => 1.0],
                ['name' => 'Materials Management', 'code' => 'MM', 'description' => 'Manages learning materials well', 'weight' => 1.0],
                ['name' => 'Perseverance', 'code' => 'PER', 'description' => 'Persists in challenging tasks', 'weight' => 1.0],
                ['name' => 'Work Quality', 'code' => 'WQ', 'description' => 'Maintains high quality of work', 'weight' => 1.0]
            ]
        ];

        // Default Behavioral Traits (10 most important)
        $defaultTraitCodes = [
            'ATT',  // Attentiveness
            'CP',   // Class Participation
            'PUNC', // Punctuality
            'NEAT', // Neatness
            'SC',   // Self-Control
            'RESP', // Respect
            'COMM', // Communication
            'TW',   // Team Work
            'EFF',  // Effort
            'INT'   // Integrity
        ];

        // Get all schools
        $schools = School::all();


        // Similarly for behavioral traits:
        foreach ($schools as $school) {
            $order = 0;
            foreach ($traits as $category => $categoryTraits) {
                foreach ($categoryTraits as $trait) {
                    BehavioralTrait::create([
                        'school_id' => $school->id,
                        'name' => $trait['name'],
                        'code' => $trait['code'],
                        'description' => $trait['description'],
                        'category' => $category,
                        'weight' => $trait['weight'],
                        'display_order' => $order++,
                        'is_default' => in_array($trait['code'], $defaultTraitCodes)
                    ]);
                }
            }
        }
    }
}

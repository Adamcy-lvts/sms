<?php

namespace Database\Seeders;

use App\Models\ExpenseItem;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategoryItemSeeder extends Seeder
{
    // Fixed expense categories 
    protected $fixedCategories = [
        'Staff Salaries' => [
            [
                'name' => 'Teacher Salary',
                'description' => 'Monthly salary for teachers',
                'unit' => 'monthly',
                'default_amount' => 150000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'monthly',
                    'category' => 'academic staff'
                ]
            ],
            [
                'name' => 'Admin Staff Salary',
                'description' => 'Monthly salary for administrative staff',
                'unit' => 'monthly',
                'default_amount' => 100000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'monthly',
                    'category' => 'admin staff'
                ]
            ]
        ],
        'Rent' => [
            [
                'name' => 'Building Rent',
                'description' => 'Annual building rental payment',
                'unit' => 'yearly',
                'default_amount' => 5000000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'yearly',
                    'building_type' => 'school premises'
                ]
            ]
        ],
        'Internet' => [
            [
                'name' => 'Internet Subscription',
                'description' => 'Monthly internet service fee',
                'unit' => 'monthly',
                'default_amount' => 50000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'monthly',
                    'service_type' => 'broadband',
                    'bandwidth' => '100Mbps'
                ]
            ]
        ],
        'Maintenance' => [
            [
                'name' => 'Building Maintenance',
                'description' => 'Regular building maintenance and repairs',
                'unit' => 'service',
                'default_amount' => 100000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'frequency' => 'quarterly',
                    'type' => 'general maintenance'
                ]
            ],
            [
                'name' => 'Equipment Maintenance',
                'description' => 'Regular maintenance of school equipment',
                'unit' => 'service',
                'default_amount' => 75000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'frequency' => 'monthly',
                    'type' => 'equipment servicing'
                ]
            ]
        ],
        'Events' => [
            [
                'name' => 'School Assembly',
                'description' => 'Weekly school assembly expenses',
                'unit' => 'event',
                'default_amount' => 10000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'frequency' => 'weekly',
                    'type' => 'regular event'
                ]
            ],
            [
                'name' => 'Special Events',
                'description' => 'Special school events and ceremonies',
                'unit' => 'event',
                'default_amount' => 250000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'frequency' => 'occasional',
                    'type' => 'special event'
                ]
            ]
        ],
        'Utilities' => [
            [
                'name' => 'Electricity Bill',
                'description' => 'Monthly electricity consumption',
                'unit' => 'monthly',
                'default_amount' => 100000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'monthly',
                    'utility_type' => 'electricity'
                ]
            ],
            [
                'name' => 'Water Bill',
                'description' => 'Monthly water consumption',
                'unit' => 'monthly',
                'default_amount' => 30000,
                'is_stock_tracked' => false,
                'minimum_quantity' => 0,
                'specifications' => [
                    'payment_type' => 'monthly',
                    'utility_type' => 'water'
                ]
            ]
        ]
    ];

    // Variable expense categories and items
    protected $variableExpenses = [
        'Teaching Materials' => [
            [
                'name' => 'Whiteboard Marker',
                'description' => 'Non-permanent markers for whiteboards',
                'unit' => 'piece',
                'default_amount' => 250,
                'is_stock_tracked' => true,
                'minimum_quantity' => 50,
                'specifications' => [
                    'brand' => 'Snowman',
                    'colors' => ['black', 'blue', 'red', 'green'],
                    'type' => 'non-permanent'
                ]
            ],
            [
                'name' => 'A4 Paper',
                'description' => 'Standard A4 size paper for printing',
                'unit' => 'rim',
                'default_amount' => 3500,
                'is_stock_tracked' => true,
                'minimum_quantity' => 10,
                'specifications' => [
                    'size' => 'A4',
                    'weight' => '80gsm',
                    'sheets_per_rim' => 500
                ]
            ],
            [
                'name' => 'Chalk',
                'description' => 'White chalk for blackboards',
                'unit' => 'box',
                'default_amount' => 1500,
                'is_stock_tracked' => true,
                'minimum_quantity' => 5,
                'specifications' => [
                    'pieces_per_box' => 100,
                    'type' => 'dustless'
                ]
            ]
        ],
        'Office Supplies' => [
            [
                'name' => 'Printer Toner',
                'description' => 'Black toner cartridge for office printer',
                'unit' => 'piece',
                'default_amount' => 25000,
                'is_stock_tracked' => true,
                'minimum_quantity' => 2,
                'specifications' => [
                    'printer_model' => 'HP LaserJet Pro',
                    'type' => 'original',
                    'yield' => '2500 pages'
                ]
            ],
            [
                'name' => 'Stapler',
                'description' => 'Heavy duty stapler',
                'unit' => 'piece',
                'default_amount' => 1500,
                'is_stock_tracked' => true,
                'minimum_quantity' => 5,
                'specifications' => [
                    'type' => 'heavy duty',
                    'capacity' => '20 sheets',
                    'staple_size' => '26/6'
                ]
            ]
        ],
        'Cleaning Supplies' => [
            [
                'name' => 'Tissue Paper',
                'description' => 'Toilet tissue paper rolls',
                'unit' => 'carton',
                'default_amount' => 7500,
                'is_stock_tracked' => true,
                'minimum_quantity' => 3,
                'specifications' => [
                    'rolls_per_carton' => 24,
                    'sheets_per_roll' => 200,
                    'ply' => 2
                ]
            ],
            [
                'name' => 'Hand Sanitizer',
                'description' => 'Alcohol-based hand sanitizer',
                'unit' => 'bottle',
                'default_amount' => 1200,
                'is_stock_tracked' => true,
                'minimum_quantity' => 10,
                'specifications' => [
                    'volume' => '500ml',
                    'alcohol_content' => '70%'
                ]
            ]
        ]
        // Add other variable expenses...
    ];

    public function run(): void
    {
        $school = \App\Models\School::where('slug', 'kings-private-school')->first();

        // Step 1: Create fixed expense categories
        // Process fixed categories with items
        foreach ($this->fixedCategories as $categoryName => $items) {
            $category = ExpenseCategory::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $categoryName
                ],
                ['type' => 'fixed']
            );

            foreach ($items as $item) {
                ExpenseItem::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'expense_category_id' => $category->id,
                        'name' => $item['name']
                    ],
                    [
                        'description' => $item['description'],
                        'unit' => $item['unit'],
                        'default_amount' => $item['default_amount'],
                        'is_stock_tracked' => $item['is_stock_tracked'],
                        'minimum_quantity' => $item['minimum_quantity'],
                        'specifications' => $item['specifications'],
                        'is_active' => true,
                        'current_stock' => 0
                    ]
                );
            }
        }

        // Step 2: Create variable categories and their items
        foreach ($this->variableExpenses as $categoryName => $items) {
            // Create category
            $category = ExpenseCategory::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'name' => $categoryName
                ],
                ['type' => 'variable']
            );

            // Create items
            foreach ($items as $item) {
                ExpenseItem::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'expense_category_id' => $category->id,
                        'name' => $item['name']
                    ],
                    [
                        'description' => $item['description'],
                        'unit' => $item['unit'],
                        'default_amount' => $item['default_amount'],
                        'is_stock_tracked' => $item['is_stock_tracked'],
                        'minimum_quantity' => $item['minimum_quantity'],
                        'specifications' => $item['specifications'],
                        'is_active' => true,
                        'current_stock' => 0
                    ]
                );
            }
        }
    }
}

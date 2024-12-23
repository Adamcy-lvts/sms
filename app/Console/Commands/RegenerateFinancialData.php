<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RegenerateFinancialData extends Command
{
    protected $signature = 'school:regenerate-financial {--school=} {--fresh}';
    protected $description = 'Regenerate financial data for testing and development';

    public function handle()
    {
        $this->info('Starting financial data regeneration...');

        try {
            if ($this->option('fresh')) {
                $this->info('Clearing existing financial records...');
                Schema::disableForeignKeyConstraints();

                DB::table('payment_types')->truncate();
                DB::table('payment_histories')->truncate();
                DB::table('payment_items')->truncate();
                DB::table('payments')->truncate();
                DB::table('expenses')->truncate();

                Schema::enableForeignKeyConstraints();
            }

            $this->info('Generating payment types...');
            $this->call('db:seed', [
                '--class' => 'PaymentTypeSeeder',
                '--force' => true,
            ]);

            $this->info('Generating payments...');
            $this->call('db:seed', [
                '--class' => 'PaymentSeeder',
                '--force' => true,
            ]);

            $this->info('Generating expenses...');
            $this->call('db:seed', [
                '--class' => 'ExpenseSeeder',
                '--force' => true,
            ]);

            $this->info('Financial data regeneration completed successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to regenerate financial data: ' . $e->getMessage());
        }
    }
}

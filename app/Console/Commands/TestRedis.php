<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class TestRedis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
  

    /**
     * The console command description.
     *
     * @var string
     */
    protected $signature = 'test:redis';
    protected $description = 'Test Redis connection and operations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Test basic Redis
            Redis::set('test_key', 'Redis Working!');
            $redisResult = Redis::get('test_key');
            
            // Test Cache facade with Redis
            Cache::put('cache_key', 'Cache Working!', 60);
            $cacheResult = Cache::get('cache_key');
            
            $this->info('Redis Test Results:');
            $this->table(
                ['Test', 'Status', 'Value'],
                [
                    ['Redis Direct', $redisResult === 'Redis Working!' ? '✅' : '❌', $redisResult],
                    ['Cache Facade', $cacheResult === 'Cache Working!' ? '✅' : '❌', $cacheResult]
                ]
            );
        } catch (\Exception $e) {
            $this->error("Redis Test Failed: " . $e->getMessage());
            Log::error("Redis Test Failed: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Term;
use Illuminate\Http\Request;
use Filament\Facades\Filament;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\Cache;

class SetAcademicPeriod
{
    // Cache duration of 24 hours since academic periods rarely change
    protected const CACHE_TTL = 86400; 

    public function handle(Request $request, Closure $next)
    {
        $tenant = Filament::getTenant();
        // Unique cache key per school
        $cacheKey = "academic_period:{$tenant->id}";
        
        // Get or cache current academic period
        $currentPeriod = Cache::tags(["school:{$tenant->slug}"])->remember(
            $cacheKey, 
            self::CACHE_TTL, 
            function () use ($tenant) {
                // Fetch current session and term if not in cache
                return [
                    'session' => $tenant->academicSessions()->where('is_current', true)->first(),
                    'term' => $tenant->terms()->where('is_current', true)->first()
                ];
            }
        );

        config([
            'app.current_session' => $currentPeriod['session'] ?? null,
            'app.current_term' => $currentPeriod['term'] ?? null,  
        ]);

        // Set global config values
        // config([
        //     'app.current_session' => $currentPeriod['session'],
        //     'app.current_term' => $currentPeriod['term'],
        // ]);

        return $next($request);
    }
}
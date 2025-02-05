<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\School;
use App\Models\Feature;
use Illuminate\Support\Facades\Log;
use App\Exceptions\FeatureException;

class FeatureService
{
    protected $limitService;

    public function checkResourceLimit(School $school, string $resource): FeatureCheckResult
    {
        try {
            $subscription = $school->currentSubscription;

            if (!$subscription) {
                return FeatureCheckResult::denied(
                    'Your school does not have an active subscription. Please subscribe to continue.'
                );
            }

            $plan = $subscription->plan;
            
            // Use specific method for staff user accounts
            if ($resource === 'staff_users') {
                return $this->checkStaffUserLimit($school, $plan);
            }

            // For other resources
            $maxAllowed = (int) $plan->{"max_{$resource}"};
            $currentCount = $school->{$resource}()->count();
            
            return $this->evaluateLimit($maxAllowed, $currentCount, $resource);
        } catch (\Exception $e) {
            Log::error("Resource limit check failed for {$resource}", [
                'school_id' => $school->id,
                'error' => $e->getMessage()
            ]);
            
            return FeatureCheckResult::denied(
                'Unable to verify resource limits. Please contact support if this persists.'
            );
        }
    }

    public function checkStaffUserLimit(School $school, Plan $plan): FeatureCheckResult
    {
        $maxAllowed = (int) $plan->max_staff;
        $currentCount = $school->staff()->whereNotNull('user_id')->count();

        Log::debug('Staff user account limit check', [
            'school_id' => $school->id,
            'max_allowed' => $maxAllowed,
            'current_count' => $currentCount,
            'plan_name' => $plan->name
        ]);

        if (!$maxAllowed) {
            return FeatureCheckResult::success();
        }

        $remaining = max(0, $maxAllowed - $currentCount);

        if ($remaining === 0) {
            return FeatureCheckResult::denied(
                "You have reached the maximum staff user accounts limit ({$maxAllowed}) for your plan. Please upgrade to add more user accounts."
            );
        }

        if ($remaining === 1) {
            return FeatureCheckResult::warning(
                "This is your last available staff user account slot in your current plan.",
                1
            );
        }

        if ($remaining <= 5) {
            return FeatureCheckResult::warning(
                "You have {$remaining} staff user account slot(s) remaining in your current plan.",
                $remaining
            );
        }

        return FeatureCheckResult::success($remaining);
    }

    protected function evaluateLimit(int $maxAllowed, int $currentCount, string $resource): FeatureCheckResult
    {
        if (!$maxAllowed) {
            return FeatureCheckResult::success();
        }

        $remaining = max(0, $maxAllowed - $currentCount);

        if ($remaining === 0) {
            return FeatureCheckResult::denied(
                $resource === 'staff_users'
                    ? "You have reached the maximum staff user accounts limit ({$maxAllowed}) for your plan. Please upgrade to add more user accounts."
                    : "You have reached the maximum {$resource} limit ({$maxAllowed}) for your plan. Please upgrade to add more."
            );
        }

        if ($remaining === 1) {
            return FeatureCheckResult::warning(
                $resource === 'staff_users'
                    ? "This is your last available staff user account slot in your current plan."
                    : "This is your last available {$resource} slot in your current plan.",
                1
            );
        }

        if ($remaining <= 5) {
            return FeatureCheckResult::warning(
                $resource === 'staff_users'
                    ? "You have {$remaining} staff user account slot(s) remaining in your current plan."
                    : "You have {$remaining} {$resource} slot(s) remaining in your current plan.",
                $remaining
            );
        }

        return FeatureCheckResult::success($remaining);
    }

    public function checkFeatureAccess(School $school, string $featureSlug): FeatureCheckResult
    {
        try {
            $subscription = $school->subscriptions()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->latest()
                ->first();

            if (!$subscription) {
                return FeatureCheckResult::denied(
                    'Your school does not have an active subscription. Please subscribe to continue.'
                );
            }

            $plan = $subscription->plan;
            
            // Check feature limits directly from plan
            switch ($featureSlug) {
                case 'students_limit':
                    if ($plan->hasLimit('student')) {
                        $currentCount = $school->students()->count();
                        if ($currentCount >= $plan->student_limit) {
                            return FeatureCheckResult::denied(
                                "You have reached the maximum student limit for your plan.",
                                $plan->student_limit - $currentCount
                            );
                        }
                    }
                    break;
                    
                case 'staff_limit':
                    if ($plan->hasLimit('staff')) {
                        $currentCount = $school->staff()->count();
                        if ($currentCount >= $plan->staff_limit) {
                            return FeatureCheckResult::denied(
                                "You have reached the maximum staff limit for your plan.",
                                $plan->staff_limit - $currentCount
                            );
                        }
                    }
                    break;
                    
                case 'classes_limit':
                    if ($plan->hasLimit('class')) {
                        $currentCount = $school->classes()->count();
                        if ($currentCount >= $plan->class_limit) {
                            return FeatureCheckResult::denied(
                                "You have reached the maximum class limit for your plan.",
                                $plan->class_limit - $currentCount
                            );
                        }
                    }
                    break;
            }

            return FeatureCheckResult::success();
            
        } catch (\Exception $e) {
            Log::error('Feature access check failed', [
                'school_id' => $school->id,
                'feature' => $featureSlug,
                'error' => $e->getMessage()
            ]);
            
            return FeatureCheckResult::denied(
                'Unable to verify feature access. Please contact support if this persists.'
            );
        }
    }

    public function hasFeatureAccess(School $school, string $featureSlug): bool
    {
        try {
            $subscription = $school->subscriptions()
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('ends_at')
                        ->orWhere('ends_at', '>', now());
                })
                ->latest()
                ->first();

            if (!$subscription) {
                return false;
            }

            return $subscription->plan->features()->where('slug', $featureSlug)->exists();
            
        } catch (\Exception $e) {
            Log::error('Feature access check failed', [
                'school_id' => $school->id,
                'feature' => $featureSlug,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_limitable'];
    
    public function plans()
    {
        return $this->belongsToMany(Plan::class, 'feature_plan')
            ->withPivot('limits');
    }


    public static function getTierLevel($slug)
    {
        return match (true) {
            in_array($slug, [
                'profile_management',
                'rbac',
                'financial_management',
                'attendance_tracking',
                'report_card_generation',
                'basic_analytics',
            ]) => 1, // Basic
            in_array($slug, [
                'admission_management',
                'email_notifications',
                'performance_analytics',
                'bulk_report_card',
                'bulk_data',
            ]) => 2, // Standard
            in_array($slug, [
                'cbt_integration',
                'portal_access',
                'advanced_reporting',
                'priority_support',
                'customization',
            ]) => 3, // Premium
            default => 0
        };
    }
}

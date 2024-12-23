<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Term;
use App\Models\School;
use App\Models\SchoolCalendarEvent;


class CalendarService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    /**

     * Get total school days for a given term excluding holidays and breaks
     */
    public function getSchoolDays(School $school, Term $term): array
    {
        // Get all events that exclude attendance in this period
        $excludedDays = $this->getExcludedDays($school, $term->start_date, $term->end_date);

        // Count weekdays between start and end date
        $totalDays = 0;
        $current = Carbon::parse($term->start_date);
        $end = Carbon::parse($term->end_date);

        while ($current <= $end) {
            if (!$current->isWeekend() && !in_array($current->format('Y-m-d'), $excludedDays)) {
                $totalDays++;
            }
            $current->addDay();
        }

        return [
            'total_days' => $totalDays,
            'excluded_dates' => $excludedDays
        ];
    }

    /**
     * Get all dates that should be excluded from school days
     */
    protected function getExcludedDays(School $school, $startDate, $endDate): array
    {
        $events = SchoolCalendarEvent::where('school_id', $school->id)
            ->where('excludes_attendance', true)
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhere('is_recurring', true);
            })->get();

        $excludedDates = [];
        foreach ($events as $event) {
            if ($event->is_recurring) {
                $excludedDates = array_merge(
                    $excludedDates,
                    $this->getRecurringDates($event, $startDate, $endDate)
                );
            } else {
                $excludedDates[] = $event->start_date->format('Y-m-d');
            }
        }

        return array_unique($excludedDates);
    }

    /**
     * Generate dates for a recurring event within a given date range
     */
    protected function getRecurringDates(SchoolCalendarEvent $event, Carbon $startDate, Carbon $endDate): array
    {
        $dates = [];
        $current = Carbon::parse($event->start_date);

        while ($current <= $endDate) {
            // Skip excluded dates and dates before start range
            if (
                $current >= $startDate &&
                !in_array($current->format('Y-m-d'), $event->excluded_dates ?? [])
            ) {
                $dates[] = $current->format('Y-m-d');
            }

            // Advance to next occurrence based on pattern
            $current = match ($event->recurrence_pattern) {
                'yearly' => $current->addYear(),
                'termly' => $current->addMonths(3),
                'monthly' => $current->addMonth(),
                default => $current->addYear()
            };
        }

        return $dates;
    }
}

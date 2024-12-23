<?php

namespace App\Observers;

use App\Models\AttendanceRecord;
use App\Filament\Sms\Resources\ClassRoomResource\Widgets\ClassRoomStatsOverview;

class AttendanceRecordObserver
{
    public function saved(AttendanceRecord $attendance)
    {
        ClassRoomStatsOverview::invalidateCache($attendance->class_room_id);
    }
}

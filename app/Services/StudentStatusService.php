<?php

namespace App\Services;

use App\Models\Status;
use App\Models\Student;
use App\Models\StudentMovement;
use Illuminate\Support\Facades\DB;

class StudentStatusService
{
    // Define which status changes should create corresponding movements
    private const STATUS_MOVEMENT_MAP = [
        'transferred' => [
            'movement_type' => 'transfer',
            'requires_destination' => true,
            'terminates_enrollment' => true,
        ],
        'withdrawn' => [
            'movement_type' => 'withdrawal',
            'requires_destination' => false,
            'terminates_enrollment' => true,
        ],
        'graduated' => [
            'movement_type' => 'graduation',
            'requires_destination' => false,
            'terminates_enrollment' => true,
        ],
    ];

    // Core statuses that don't create movements
    private const CORE_STATUSES = [
        'active',
        'inactive',
        'suspended',
        'expelled',
        'deceased',
    ];

    public function changeStatus(Student $student, int $newStatusId, string $reason = '', ?array $additionalData = null): void
    {
        $oldStatusId = $student->status_id;
        $newStatus = Status::findOrFail($newStatusId);
        
        DB::transaction(function () use ($student, $oldStatusId, $newStatusId, $newStatus, $reason, $additionalData) {
            // Always record the status change
            $student->statusChanges()->create([
                'school_id' => $student->school_id,
                'student_id' => $student->id,
                'from_status_id' => $oldStatusId,
                'to_status_id' => $newStatusId,
                'reason' => $reason ?? 'No reason provided',
                'changed_by' => auth()->id(),
                'changed_at' => now(),
                'metadata' => $additionalData,
            ]);

            // Create movement record if status requires it
            if (isset(self::STATUS_MOVEMENT_MAP[$newStatus->name])) {
                $config = self::STATUS_MOVEMENT_MAP[$newStatus->name];
                
                StudentMovement::create([
                    'student_id' => $student->id,
                    // 'school_id' => $student->school_id,
                    'from_class_id' => $student->class_room_id,
                    'to_class_id' => null,
                    'from_session_id' => config('app.current_session')->id,
                    'to_session_id' => null,
                    'movement_type' => $config['movement_type'],
                    'movement_date' => now(),
                    'reason' => $reason ?? 'No reason provided',
                    'status' => 'completed',
                    'processed_by' => auth()->id(),
                ]);
            }

            // Update the student status and handle class room assignment
            $updateData = ['status_id' => $newStatusId];
            
            // If student is graduated, set class_room_id to null
            if ($newStatus->name === 'graduated') {
                $updateData['class_room_id'] = null;
            }
            
            $student->update($updateData);
        });
    }
}

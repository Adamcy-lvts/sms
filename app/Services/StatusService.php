<?php

namespace App\Services;

use App\Models\Status;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class StatusService
{
    // Cache duration in seconds (24 hours)
    const CACHE_DURATION = 86400;

    // Status Types
    const TYPE_GENERAL = 'general';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ADMISSION = 'admission';
    const TYPE_STUDENT = 'student';
    const TYPE_STAFF = 'staff';

    // Common Status Names
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get status ID by type and name
     */
    public function getStatusId(string $type, string $name): ?int
    {
        $cacheKey = "status_{$type}_{$name}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($type, $name) {
            return Status::where('type', $type)
                ->where('name', $name)
                ->value('id');
        });
    }

    /**
     * Get all statuses by type
     */
    public function getStatusesByType(string $type): Collection
    {
        $cacheKey = "statuses_{$type}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($type) {
            return Status::where('type', $type)->get();
        });
    }

    /**
     * Student Status Methods
     */
    public function getActiveStudentStatusId(): ?int
    {
        return $this->getStatusId(self::TYPE_STUDENT, self::STATUS_ACTIVE);
    }

    public function getStudentStatusId(string $name): ?int
    {
        return $this->getStatusId(self::TYPE_STUDENT, $name);
    }

    public function getAllStudentStatuses(): Collection
    {
        return $this->getStatusesByType(self::TYPE_STUDENT);
    }

    /**
     * Staff Status Methods
     */
    public function getActiveStaffStatusId(): ?int
    {
        return $this->getStatusId(self::TYPE_STAFF, self::STATUS_ACTIVE);
    }

    public function getStaffStatusId(string $name): ?int
    {
        return $this->getStatusId(self::TYPE_STAFF, $name);
    }

    public function getAllStaffStatuses(): Collection
    {
        return $this->getStatusesByType(self::TYPE_STAFF);
    }

    /**
     * Payment Status Methods
     */
    public function getPaymentStatusId(string $name): ?int
    {
        return $this->getStatusId(self::TYPE_PAYMENT, $name);
    }

    public function getAllPaymentStatuses(): Collection
    {
        return $this->getStatusesByType(self::TYPE_PAYMENT);
    }

    /**
     * Admission Status Methods
     */
    public function getAdmissionStatusId(string $name): ?int
    {
        return $this->getStatusId(self::TYPE_ADMISSION, $name);
    }

    public function getAllAdmissionStatuses(): Collection
    {
        return $this->getStatusesByType(self::TYPE_ADMISSION);
    }

    /**
     * General Status Methods
     */
    public function getGeneralStatusId(string $name): ?int
    {
        return $this->getStatusId(self::TYPE_GENERAL, $name);
    }

    public function getAllGeneralStatuses(): Collection
    {
        return $this->getStatusesByType(self::TYPE_GENERAL);
    }

    /**
     * Utility Methods
     */
    public function isStudentActive(int $statusId): bool
    {
        return $statusId === $this->getActiveStudentStatusId();
    }

    public function isStaffActive(int $statusId): bool
    {
        return $statusId === $this->getActiveStaffStatusId();
    }

    /**
     * Clear all status caches
     */
    public function clearCache(): void
    {
        $types = [
            self::TYPE_GENERAL,
            self::TYPE_PAYMENT,
            self::TYPE_ADMISSION,
            self::TYPE_STUDENT,
            self::TYPE_STAFF
        ];

        foreach ($types as $type) {
            Cache::forget("statuses_{$type}");
            
            // Clear individual status caches for this type
            $statuses = Status::where('type', $type)->get();
            foreach ($statuses as $status) {
                Cache::forget("status_{$type}_{$status->name}");
            }
        }
    }
}
<?php

namespace App\Services;

class FeatureCheckResult
{
    public bool $allowed;
    public string $message;
    public int $remaining;
    public string $status;

    private function __construct(bool $allowed, string $message = '', int $remaining = 0, string $status = 'success')
    {
        $this->allowed = $allowed;
        $this->message = $message;
        $this->remaining = $remaining;
        $this->status = $status;
    }

    public static function success(int $remaining = PHP_INT_MAX): self
    {
        return new self(true, '', $remaining);
    }

    public static function denied(string $message, int $remaining = 0): self
    {
        return new self(false, $message, $remaining, 'error');
    }

    public static function warning(string $message, int $remaining): self
    {
        return new self(true, $message, $remaining, 'warning');
    }
}

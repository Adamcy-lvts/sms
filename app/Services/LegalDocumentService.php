<?php

namespace App\Services;

use App\Models\LegalDocument;
use Illuminate\Support\Facades\Cache;

class LegalDocumentService
{
    public function getActiveDocument(string $type): ?LegalDocument
    {
        return Cache::remember("legal_document_{$type}", 3600, function () use ($type) {
            return LegalDocument::where('type', $type)
                ->where('is_active', true)
                ->latest('published_at')
                ->first();
        });
    }

    public function getTermsUrl(): string
    {
        return route('legal.show', 'terms');
    }

    public function getPrivacyUrl(): string
    {
        return route('legal.show', 'privacy');
    }
}

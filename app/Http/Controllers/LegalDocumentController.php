<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LegalDocument;

class LegalDocumentController extends Controller
{
    public function show($type)
    {
    
        $document = LegalDocument::where('type', $type)
            ->where('is_active', true)
            ->latest('published_at')
            ->firstOrFail();
            // dd($type);
        return view('legal-documents.show', [
            'document' => $document,
        ]);
    }
}
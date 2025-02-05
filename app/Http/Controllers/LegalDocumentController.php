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

    // In your controller
    // public function show($type)
    // {
    //     $document = LegalDocument::where('type', $type)
    //         ->where('is_active', true)
    //         ->latest('published_at')
    //         ->firstOrFail();

    //     // Add TipTap specific classes
    //     $content = tiptap_converter()
    //         ->withClasses([
    //             'paragraph' => 'mb-4 leading-relaxed',
    //             'heading' => 'font-bold mb-4',
    //             'bulletList' => 'list-disc pl-6 mb-4'
    //         ])
    //         ->asHTML($document->content);

    //     return view('legal-documents.show', [
    //         'document' => $document,
    //         'content' => $content
    //     ]);
    // }
}

<?php

namespace App\Services;

use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Storage;

class PdfGeneratorService
{
    public function generate(
        string $view,
        array $data = [],
        array $options = [],
        bool $save = false,
        ?string $path = null
    ): string {
        $pdf = Pdf::view($view, $data)
            ->format($options['format'] ?? 'a4')
            ->withBrowsershot(function (Browsershot $browsershot) use ($options) {
                $browsershot->setChromePath(config('app.chrome_path'))
                    ->format($options['format'] ?? 'A4')
                    ->emulateMedia($options['media'] ?? 'print')
                    ->screenResolution($options['width'] ?? 1280, $options['height'] ?? 1024)
                    ->scale($options['scale'] ?? 1)
                    ->margins(
                        $options['margin_top'] ?? 0,
                        $options['margin_right'] ?? 0,
                        $options['margin_bottom'] ?? 0,
                        $options['margin_left'] ?? 0
                    )
                    ->showBackground()
                    ->waitUntilNetworkIdle();

                // Apply any additional options
                if (isset($options['landscape']) && $options['landscape']) {
                    $browsershot->landscape();
                }
                if (isset($options['grayscale']) && $options['grayscale']) {
                    $browsershot->grayscale();
                }
            });

        if ($save) {
            if (!$path) {
                $directory = $options['directory'] ?? 'documents';
                // Fix filename generation to preserve full admission number
                $filename = $options['filename'] ?? Str::uuid() . '.pdf';
                
                // Ensure proper path construction
                $path = trim($directory, '/') . '/' . trim($filename, '/');
                
                // Ensure path starts with public/
                if (!Str::startsWith($path, 'public/')) {
                    $path = 'public/' . $path;
                }
            }

            // Log the file path for debugging
            Log::info('Generating PDF', [
                'path' => $path,
                'filename' => $filename ?? null,
                'directory' => $directory ?? null
            ]);

            // Ensure directory exists
            $directory = dirname(Storage::path($path));
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            $pdf->save(Storage::path($path));
            return $path;
        }

        return $pdf->toString();
    }

    public function download(string $content, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function stream(string $content, string $filename): \Symfony\Component\HttpFoundation\Response
    {
        return response($content)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', "inline; filename=\"{$filename}\"");
    }
}

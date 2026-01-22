<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Log;

class GoogleVisionOcrService
{
    public static function extractText(string $absoluteImagePath): ?string
{
    Log::info('OCR START', [
        'path' => $absoluteImagePath,
        'exists' => file_exists($absoluteImagePath),
        'env' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ]);

    try {
        $client = new ImageAnnotatorClient([
            'credentials' => base_path(env('GOOGLE_APPLICATION_CREDENTIALS')),
        ]);

        $imageContent = file_get_contents($absoluteImagePath);

        $response = $client->textDetection($imageContent);
        $texts = $response->getTextAnnotations();

        $client->close();

        Log::info('OCR RESULT', [
            'count' => count($texts)
        ]);

        if (!empty($texts)) {
            return $texts[0]->getDescription();
        }

        return null;

    } catch (\Throwable $e) {
        Log::error('Google Vision OCR Error', [
            'message' => $e->getMessage(),
        ]);
        return null;
    }
}

}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiRequestAnalyzer
{
    /**
     * Analyze supporting document using OpenAI Vision (NO OCR)
     */
    public static function analyzeDocument(string $filePath, array $context = []): array
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('Document file not found');
            }

            $base64 = base64_encode(file_get_contents($filePath));
            $mime   = mime_content_type($filePath) ?: 'image/jpeg';

            $prompt = <<<PROMPT
You are an AI assistant helping an administrator review supporting documents for help requests.

Your tasks:
1. Identify the document type (e.g. payslip, utility bill, medical letter).
2. Extract only relevant non-sensitive information.
3. Generate a short 1–2 sentence summary to assist admin decision.
4. Provide a confidence score (0–100).
5. Identify the document issue date or statement date if available.

DO NOT include sensitive personal identifiers.
Respond STRICTLY in JSON format:

{
  "document_type": "",
  "document_date": "",
  "extracted_data": {},
  "summary": "",
  "confidence": 0
}
PROMPT;

            $response = Http::withToken(config('services.openai.key'))
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o-mini',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:$mime;base64,$base64",
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 600,
                ]);

            if (!$response->successful()) {
                Log::error('OPENAI VISION HTTP ERROR', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return [];
            }

            $content = $response->json('choices.0.message.content');

            if (!$content) {
                Log::error('OPENAI EMPTY RESPONSE', $response->json());
                return [];
            }

            // 🔥 IMPORTANT: remove ```json ``` wrapper if exists
            $content = trim($content);
            $content = preg_replace('/^```json|```$/m', '', $content);

            $decoded = json_decode($content, true);

            if (!is_array($decoded)) {
                Log::error('OPENAI INVALID JSON', ['content' => $content]);
                return [];
            }

            return $decoded;

        } catch (\Exception $e) {
            Log::error('AI ANALYSIS EXCEPTION', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }
}

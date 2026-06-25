<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAnalysisService
{
    public function __construct()
    {
        $this->ocrUrl = config('services.ocr.url');
        $this->nlpUrl = config('services.nlp.url');
        
        if (empty($this->ocrUrl)) {
            Log::warning('OCR Service URL is not configured. Check SERVICES_OCR_URL in .env');
        }
        
        if (empty($this->nlpUrl)) {
            Log::warning('NLP Service URL is not configured. Check SERVICES_NLP_URL in .env');
        }
    }

    private string $ocrUrl;
    private string $nlpUrl;

    public function extractText(string $imageUrl, array $fieldLabels = []): array
    {
        try {
            $response = Http::timeout(30)
                ->post("{$this->ocrUrl}/ocr/extract", [
                    'image_url'    => $imageUrl,
                    'field_labels' => $fieldLabels,
                ]);

            if ($response->successful()) {
                Log::info('OCR berhasil', ['url' => $imageUrl]);
                return $response->json();
            }

            Log::warning('OCR gagal', ['status' => $response->status()]);
            return $this->emptyOcrResult();

        } catch (\Exception $e) {
            Log::error('OCR error: ' . $e->getMessage());
            return $this->emptyOcrResult();
        }
    }

    public function matchFields(string $ocrText, array $fields): array
    {
        try {
            $response = Http::timeout(15)
                ->post("{$this->nlpUrl}/nlp/match", [
                    'ocr_text' => $ocrText,
                    'fields'   => $fields,
                ]);

            if ($response->successful()) {
                $result = $response->json();
                $result['results'] = $this->filterValidMatches(
                    $result['results'] ?? [],
                    $fields
                );
                return $result;
            }

            Log::warning('NLP match gagal', ['status' => $response->status()]);
            return $this->emptyNlpResult();

        } catch (\Exception $e) {
            Log::error('NLP error: ' . $e->getMessage());
            return $this->emptyNlpResult();
        }
    }

    public function analyzeEvidence(string $imageUrl, array $fields): array
    {
        $ocrResult = $this->extractText($imageUrl, array_column($fields, 'label'));

        if (!$ocrResult['success']) {
            return [
                'success'  => false,
                'ocr_text' => null,
                'matches'  => [],
                'summary'  => null,
            ];
        }

        $nlpResult = $this->matchFields($ocrResult['full_text'], $fields);

        return [
            'success'  => true,
            'ocr_text' => $ocrResult['full_text'],
            'matches'  => $nlpResult['results'] ?? [],
            'summary'  => $nlpResult['summary'] ?? null,
        ];
    }

    private function filterValidMatches(array $results, array $validFields): array
    {
        $validKeys = array_column($validFields, 'key');
        $validLabels = array_column($validFields, 'label');

        return array_values(array_filter($results, function ($match) use ($validKeys, $validLabels) {
            $field = $match['field'] ?? $match['key'] ?? '';
            $label = $match['label'] ?? '';

            // Skip field sampah OCR (panjang < 3 karakter)
            if (strlen($field) < 3 && strlen($label) < 3) {
                return false;
            }

            return in_array($field, $validKeys) || in_array($label, $validLabels);
        }));
    }

    private function emptyOcrResult(): array
    {
        return ['success' => false, 'full_text' => null, 'matches' => []];
    }

    private function emptyNlpResult(): array
    {
        return ['success' => false, 'results' => [], 'summary' => null];
    }
}
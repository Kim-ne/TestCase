<?php

namespace App\Services\FileExtraction;

use App\Services\Contracts\FileTextExtractorInterface;

class TextNormalizerService
{
    /**
     * @param array<int, FileTextExtractorInterface> $extractors
     */
    public function __construct(protected array $extractors = [])
    {
    }

    public function normalize(?string $text, ?string $filePath, ?string $extension): string
    {
        if (! empty($text)) {
            return trim($text);
        }

        if (empty($filePath) || empty($extension)) {
            return '';
        }

        $extension = strtolower($extension);

        foreach ($this->extractors as $extractor) {
            if (! $extractor->supports($extension)) {
                continue;
            }

            try {
                $extractedText = trim($extractor->extract($filePath));

                if ($extractedText !== '') {
                    return $extractedText;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return '';
    }
}

<?php

namespace App\Services\FileExtraction;

use App\Exceptions\InvalidRequirementContentException;
use App\Services\Contracts\FileTextExtractorInterface;
use Throwable;

class TextNormalizerService
{
    /**
     * @param  array<int, FileTextExtractorInterface>  $extractors
     */
    public function __construct(protected array $extractors = []) {}

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
            } catch (Throwable $exception) {
                throw new InvalidRequirementContentException(
                    'The uploaded file could not be extracted.',
                    previous: $exception,
                );
            }

            if ($extractedText === '') {
                throw new InvalidRequirementContentException(
                    'The uploaded file does not contain readable text.',
                );
            }

            return $extractedText;
        }

        throw new InvalidRequirementContentException(
            'The uploaded file type is not supported.',
        );
    }
}

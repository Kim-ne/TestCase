<?php

namespace App\Services\FileExtraction;

use App\Services\Contracts\FileTextExtractorInterface;
use Closure;
use PhpOffice\PhpWord\IOFactory;
use Stringable;

class WordTextExtractor implements FileTextExtractorInterface
{
    public function __construct(protected ?Closure $documentLoader = null) {}

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['doc', 'docx'], true);
    }

    public function extract(string $path): string
    {
        $phpWord = $this->documentLoader === null
            ? IOFactory::load($path)
            : ($this->documentLoader)($path);
        $parts = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text = $this->extractElementText($element);

                if ($text !== '') {
                    $parts[] = $text;
                }
            }
        }

        return trim(implode(PHP_EOL, $parts));
    }

    private function extractElementText(object $element): string
    {
        foreach (['getElements', 'getRows', 'getCells'] as $childrenMethod) {
            if (! method_exists($element, $childrenMethod)) {
                continue;
            }

            $parts = [];

            foreach ($element->{$childrenMethod}() as $child) {
                if (! is_object($child)) {
                    continue;
                }

                $text = $this->extractElementText($child);

                if ($text !== '') {
                    $parts[] = $text;
                }
            }

            if ($parts !== []) {
                return implode(PHP_EOL, $parts);
            }
        }

        if (! method_exists($element, 'getText')) {
            return '';
        }

        $text = $element->getText();

        return is_string($text) || $text instanceof Stringable
            ? trim((string) $text)
            : '';
    }
}

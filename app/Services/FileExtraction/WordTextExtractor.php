<?php

namespace App\Services\FileExtraction;

use App\Services\Contracts\FileTextExtractorInterface;
use PhpOffice\PhpWord\IOFactory;

class WordTextExtractor implements FileTextExtractorInterface
{
    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['doc', 'docx'], true);
    }

    public function extract(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . PHP_EOL;
                }
            }
        }

        return trim($text);
    }
}

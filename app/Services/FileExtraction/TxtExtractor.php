<?php

namespace App\Services\FileExtraction;

use App\Services\Contracts\FileTextExtractorInterface;
use RuntimeException;

class TxtExtractor implements FileTextExtractorInterface
{
    public function supports(string $extension): bool
    {
        return strtolower($extension) === 'txt';
    }

    public function extract(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('The text file is not readable.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('The text file could not be read.');
        }

        return $contents;
    }
}

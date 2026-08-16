<?php

namespace App\Services\FileExtraction;

use App\Services\Contracts\FileTextExtractorInterface;
use Smalot\PdfParser\Parser;

class PdfTextExtractor implements FileTextExtractorInterface
{
    public function __construct(protected ?Parser $parser = null) {}

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), ['pdf'], true);
    }

    public function extract(string $path): string
    {
        $parser = $this->parser ?? new Parser;

        $pdf = $parser->parseFile($path);

        return trim($pdf->getText() ?? '');
    }
}

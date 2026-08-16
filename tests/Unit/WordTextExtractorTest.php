<?php

namespace Tests\Unit;

use App\Services\FileExtraction\WordTextExtractor;
use PHPUnit\Framework\TestCase;

class WordTextExtractorTest extends TestCase
{
    public function test_it_extracts_text_from_nested_word_elements(): void
    {
        $text = new class
        {
            public function getText(): string
            {
                return 'Nested table content';
            }
        };

        $cell = new class($text)
        {
            public function __construct(private readonly object $text) {}

            public function getElements(): array
            {
                return [$this->text];
            }
        };

        $row = new class($cell)
        {
            public function __construct(private readonly object $cell) {}

            public function getCells(): array
            {
                return [$this->cell];
            }
        };

        $table = new class($row)
        {
            public function __construct(private readonly object $row) {}

            public function getRows(): array
            {
                return [$this->row];
            }
        };

        $section = new class($table)
        {
            public function __construct(private readonly object $table) {}

            public function getElements(): array
            {
                return [$this->table];
            }
        };

        $document = new class($section)
        {
            public function __construct(private readonly object $section) {}

            public function getSections(): array
            {
                return [$this->section];
            }
        };

        $extractor = new WordTextExtractor(fn (string $path): object => $document);

        $this->assertSame('Nested table content', $extractor->extract('document.docx'));
    }
}

<?php

namespace Tests\Unit;

use App\Services\FileExtraction\TxtExtractor;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TxtExtractorTest extends TestCase
{
    public function test_it_supports_txt_extensions_case_insensitively(): void
    {
        $extractor = new TxtExtractor;

        $this->assertTrue($extractor->supports('txt'));
        $this->assertTrue($extractor->supports('TXT'));
        $this->assertFalse($extractor->supports('pdf'));
    }

    public function test_it_reads_the_contents_of_a_text_file(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'autogen-txt-');
        $this->assertNotFalse($path);
        file_put_contents($path, "First line\nSecond line");

        try {
            $extractor = new TxtExtractor;

            $this->assertSame("First line\nSecond line", $extractor->extract($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_it_throws_when_the_text_file_is_not_readable(): void
    {
        $extractor = new TxtExtractor;

        $this->expectException(RuntimeException::class);

        $extractor->extract(sys_get_temp_dir().DIRECTORY_SEPARATOR.'missing-file.txt');
    }
}

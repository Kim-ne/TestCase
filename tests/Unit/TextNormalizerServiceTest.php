<?php

namespace Tests\Unit;

use App\Services\Contracts\FileTextExtractorInterface;
use App\Services\FileExtraction\TextNormalizerService;
use App\Ai\Agents\TestCaseGeneratorAgent;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TextNormalizerServiceTest extends TestCase
{
    public function test_it_returns_raw_text_when_text_input_is_present(): void
    {
        $service = new TextNormalizerService([]);

        $this->assertSame('hello prompt', $service->normalize('hello prompt', null, null));
    }

    public function test_it_uses_the_matching_extractor_for_supported_files(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tempFile, 'docx content');

        $service = new TextNormalizerService([
            new class implements FileTextExtractorInterface {
            public function supports(string $extension): bool
            {
                return $extension === 'pdf';
            }

            public function extract(string $path): string
            {
                return 'pdf text';
            }
            },
            new class implements FileTextExtractorInterface {
            public function supports(string $extension): bool
            {
                return $extension === 'docx';
            }

            public function extract(string $path): string
            {
                return 'word text';
            }
            },
        ]);

        $this->assertSame('word text', $service->normalize(null, $tempFile, 'docx'));

        unlink($tempFile);
    }

    public function test_it_returns_an_empty_string_when_no_input_is_present(): void
    {
        $service = new TextNormalizerService([]);

        $this->assertSame('', $service->normalize(null, null, null));
    }

    public function test_it_returns_empty_string_when_no_extractor_supports_the_extension(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'txt');
        file_put_contents($tempFile, 'some content');

        $service = new TextNormalizerService([
            new class implements FileTextExtractorInterface {
            public function supports(string $extension): bool
            {
                return $extension === 'pdf';
            }

            public function extract(string $path): string
            {
                return 'pdf text';
            }
            },
            new class implements FileTextExtractorInterface {
            public function supports(string $extension): bool
            {
                return $extension === 'docx';
            }

            public function extract(string $path): string
            {
                return 'word text';
            }
            },
        ]);

        $this->assertSame('', $service->normalize(null, $tempFile, 'txt'));

        unlink($tempFile);
    }

    public function test_it_trims_text(): void
    {
        $service = new TextNormalizerService([]);

        $this->assertSame('hello', $service->normalize('  hello  ', null, null));
    }

    public function test_it_prioritizes_text_over_file_when_both_are_present(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tempFile, 'docx content');

        $service = new TextNormalizerService([
            new class implements FileTextExtractorInterface {
                public function supports(string $extension): bool
                {
                    return $extension === 'docx';
                }

                public function extract(string $path): string
                {
                    return 'word text from file';
                }
            },
        ]);

        $this->assertSame('hello from text',
                $service->normalize('hello from text', $tempFile, 'docx'));

        unlink($tempFile);
    }
}

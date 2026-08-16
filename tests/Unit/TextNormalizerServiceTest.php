<?php

namespace Tests\Unit;

use App\Exceptions\InvalidRequirementContentException;
use App\Services\Contracts\FileTextExtractorInterface;
use App\Services\FileExtraction\TextNormalizerService;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class TextNormalizerServiceTest extends TestCase
{
    public function test_it_returns_trimmed_text_when_text_input_is_present(): void
    {
        $service = new TextNormalizerService([]);

        $this->assertSame('hello prompt', $service->normalize('  hello prompt  ', null, null));
    }

    public function test_it_uses_the_matching_extractor_for_supported_files(): void
    {
        $service = new TextNormalizerService([
            $this->extractor('pdf', 'pdf text'),
            $this->extractor('docx', 'word text'),
            $this->extractor('txt', 'text text'),
        ]);

        $this->assertSame('word text', $service->normalize(null, 'document-path', 'DOCX'));
    }

    public function test_it_returns_an_empty_string_when_no_input_is_present(): void
    {
        $service = new TextNormalizerService([]);

        $this->assertSame('', $service->normalize(null, null, null));
    }

    public function test_it_rejects_unsupported_file_extensions(): void
    {
        $service = new TextNormalizerService([
            $this->extractor('pdf', 'pdf text'),
        ]);

        $this->expectException(InvalidRequirementContentException::class);

        $service->normalize(null, 'document-path', 'txt');
    }

    public function test_it_rejects_files_without_readable_text(): void
    {
        $service = new TextNormalizerService([
            $this->extractor('pdf', '   '),
        ]);

        $this->expectException(InvalidRequirementContentException::class);

        $service->normalize(null, 'document-path', 'pdf');
    }

    public function test_it_wraps_extractor_failures_in_a_safe_domain_exception(): void
    {
        $extractor = new class implements FileTextExtractorInterface
        {
            public function supports(string $extension): bool
            {
                return $extension === 'pdf';
            }

            public function extract(string $path): string
            {
                throw new RuntimeException('Parser implementation detail');
            }
        };

        $service = new TextNormalizerService([$extractor]);

        try {
            $service->normalize(null, 'document-path', 'pdf');
            $this->fail('Expected the extractor failure to be wrapped.');
        } catch (InvalidRequirementContentException $exception) {
            $this->assertSame('The uploaded file could not be extracted.', $exception->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $exception->getPrevious());
        }
    }

    public function test_it_prioritizes_text_over_file_when_both_are_present(): void
    {
        $service = new TextNormalizerService([
            $this->extractor('docx', 'word text from file'),
        ]);

        $this->assertSame(
            'hello from text',
            $service->normalize('hello from text', 'document-path', 'docx'),
        );
    }

    private function extractor(string $supportedExtension, string $extractedText): FileTextExtractorInterface
    {
        return new class($supportedExtension, $extractedText) implements FileTextExtractorInterface
        {
            public function __construct(
                private readonly string $supportedExtension,
                private readonly string $extractedText,
            ) {}

            public function supports(string $extension): bool
            {
                return $extension === $this->supportedExtension;
            }

            public function extract(string $path): string
            {
                return $this->extractedText;
            }
        };
    }
}

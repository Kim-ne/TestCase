<?php

namespace App\Services;

use App\Ai\Agents\TestCaseGeneratorAgent;
use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Jobs\GenerateTestCasesJob;
use App\Models\TestGenerationRequest;
use App\Repositories\Contracts\TestGenerationRequestRepositoryInterface;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use App\Services\FileExtraction\TextNormalizerService;
use Illuminate\Support\Facades\Log;
use Throwable;

class TestCaseGeneratorService implements TestCaseGeneratorServiceInterface
{
    private const MAX_REQUIREMENT_LENGTH = 50000;

    public function __construct(
        protected TextNormalizerService $normalizer,
        protected TestCaseGeneratorAgent $agent,
        protected TestGenerationRequestRepositoryInterface $generationRequests,
    ) {}

    public function generate(?string $text, ?string $filePath, ?string $extension, string $language): array
    {
        $generationRequest = $this->createRequestFromInput($text, $filePath, $extension, $language);

        return $this->generateForRequest($generationRequest);
    }

    public function queue(?string $text, ?string $filePath, ?string $extension, string $language): TestGenerationRequest
    {
        $generationRequest = $this->createRequestFromInput($text, $filePath, $extension, $language);

        try {
            GenerateTestCasesJob::dispatch($generationRequest->id);

            return $generationRequest;
        } catch (Throwable $exception) {
            $this->markGenerationRequestAsFailed($generationRequest);

            throw new TestCaseGenerationFailedException(
                'The test case generation request could not be queued.',
                previous: $exception,
            );
        }
    }

    public function generateForRequest(TestGenerationRequest $generationRequest): array
    {
        try {
            $filePath = $generationRequest->input_type === 'file'
                ? $generationRequest->input_file_path
                : null;
            $plainText = $this->requirementContent(
                $generationRequest->input_type === 'text' ? $generationRequest->input_text : null,
                $filePath,
                $filePath ? pathinfo($filePath, PATHINFO_EXTENSION) : null,
            );
            [, $languageName] = $this->resolveLanguage($generationRequest->output_language);
            $response = $this->agent->prompt($this->buildMessage($plainText, $languageName));
            $testCases = $this->extractTestCases($response->structured ?? []);
            $normalizedTestCases = $this->normalizeTestCases($testCases);

            if ($normalizedTestCases === []) {
                throw new TestCaseGenerationFailedException(
                    'The AI response did not contain valid test cases.',
                );
            }

            $this->generationRequests->markAsCompleted($generationRequest, $normalizedTestCases);

            return $normalizedTestCases;
        } catch (InvalidRequirementContentException|TestCaseGenerationFailedException $exception) {
            $this->markGenerationRequestAsFailed($generationRequest);

            throw $exception;
        } catch (Throwable $exception) {
            $this->markGenerationRequestAsFailed($generationRequest);

            throw new TestCaseGenerationFailedException(
                'The generated test cases could not be saved.',
                previous: $exception,
            );
        }
    }

    protected function createRequestFromInput(
        ?string $text,
        ?string $filePath,
        ?string $extension,
        string $language,
    ): TestGenerationRequest {
        $plainText = $this->requirementContent($text, $filePath, $extension);
        [$outputLanguage] = $this->resolveLanguage($language);

        try {
            return $this->createGenerationRequest($plainText, $filePath, $outputLanguage);
        } catch (Throwable $exception) {
            throw new TestCaseGenerationFailedException(
                'The test case generation request could not be saved.',
                previous: $exception,
            );
        }
    }

    protected function requirementContent(?string $text, ?string $filePath, ?string $extension): string
    {
        $plainText = $this->normalizer->normalize($text, $filePath, $extension);

        if ($plainText === '') {
            throw new InvalidRequirementContentException(
                'The provided requirement does not contain readable text.',
            );
        }

        if (mb_strlen($plainText) > self::MAX_REQUIREMENT_LENGTH) {
            throw new InvalidRequirementContentException(
                'The extracted requirement content is too long.',
            );
        }

        return $plainText;
    }

    /**
     * @return array{0: 'en'|'vi', 1: 'English'|'Vietnamese'}
     */
    protected function resolveLanguage(string $language): array
    {
        return match (strtolower(trim($language))) {
            'vi', 'vietnamese' => ['vi', 'Vietnamese'],
            default => ['en', 'English'],
        };
    }

    protected function createGenerationRequest(
        string $plainText,
        ?string $filePath,
        string $outputLanguage,
    ): TestGenerationRequest {
        $isFileInput = $filePath !== null && $filePath !== '';

        return $this->generationRequests->create([
            'input_type' => $isFileInput ? 'file' : 'text',
            'input_text' => $isFileInput ? null : $plainText,
            'input_file_path' => $isFileInput ? $filePath : null,
            'output_language' => $outputLanguage,
            'provider' => config('ai.default', 'gemini'),
        ]);
    }

    protected function markGenerationRequestAsFailed(TestGenerationRequest $generationRequest): void
    {
        try {
            $this->generationRequests->markAsFailed(
                $generationRequest,
                'Test case generation failed.',
            );
        } catch (Throwable $exception) {
            // Preserve the original error response if the status update cannot be saved.
            Log::error('The test case generation request could not be marked as failed.',[
                'request_id' => $generationRequest->id,
                'error' => $exception->getMessage(),
                'exception' => $exception
            ]);
        }
    }

    protected function extractTestCases(mixed $structured): array
    {
        if (! is_array($structured)) {
            return [];
        }

        // Standard case
        if (isset($structured['test_cases']) && is_array($structured['test_cases'])) {
            return array_values(array_filter(
                $structured['test_cases'],
                fn ($item) => is_array($item) && $this->isSingleTestCase($item),
            ));
        }

        // AI returns single test case
        if ($this->isSingleTestCase($structured)) {
            return [$structured];
        }

        // AI returns multiple test cases
        return array_values(array_filter(
            $structured,
            fn ($item) => is_array($item) && $this->isSingleTestCase($item),
        ));
    }

    protected function isSingleTestCase(array $payload): bool
    {
        return isset(
            $payload['title'],
            $payload['steps'],
            $payload['expected_result'],
            $payload['priority'],
        )
            && array_key_exists('preconditions', $payload)
            && is_string($payload['title'])
            && is_array($payload['steps'])
            && is_string($payload['expected_result'])
            && is_string($payload['priority']);
    }

    protected function normalizeTestCases(array $testCases): array
    {
        $mapping = [
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
        ];

        $normalizedTestCases = array_map(function (array $testCase) use ($mapping): ?array {
            $priority = $mapping[strtolower(trim($testCase['priority']))] ?? null;

            if ($priority === null) {
                return null;
            }

            $title = trim($testCase['title']);
            $expectedResult = trim($testCase['expected_result']);
            $steps = array_values(array_filter(
                array_map(
                    fn (mixed $step): ?string => is_string($step) ? trim($step) : null,
                    $testCase['steps'],
                ),
                fn (?string $step): bool => $step !== null && $step !== '',
            ));

            if ($title === '' || $expectedResult === '' || $steps === []) {
                return null;
            }

            $testCase['title'] = $title;
            $testCase['preconditions'] = is_string($testCase['preconditions'])
                ? trim($testCase['preconditions'])
                : '';
            $testCase['steps'] = $steps;
            $testCase['expected_result'] = $expectedResult;
            $testCase['priority'] = $priority;

            return $testCase;
        }, $testCases);

        return array_values(array_filter($normalizedTestCases));
    }

    protected function buildMessage(string $requirementText, string $language): string
    {
        return trim($requirementText)."\n\n"
            ."Generate detailed test cases based on the requirement above.\n"
            ."Language of the test cases: {$language}.";
    }
}

<?php

namespace App\Services;

use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use App\Services\FileExtraction\TextNormalizerService;
use App\Ai\Agents\TestCaseGeneratorAgent;
use App\Exceptions\TestCaseGenerationFailedException;

class TestCaseGeneratorService implements TestCaseGeneratorServiceInterface
{
    public function __construct(
        protected TextNormalizerService $normalizer,
    ) {
    }

    public function generate(?string $text, ?string $filePath, ?string $extension, string $language): array
    {
        $plainText = $this->normalizer->normalize($text, $filePath, $extension);
        if (empty($plainText)) {
            return [];
        }
        $language = match(strtolower($language)){
            'en', 'english' => 'English',
            'vi', 'vietnamese' => 'Vietnamese',
            default => 'English',
        };
        $message = $this->buildMessage($plainText, $language);

        $agent = new TestCaseGeneratorAgent();

        try {
            $response = $agent->prompt($message);
        } catch (\Throwable $e) {
            throw new TestCaseGenerationFailedException(
                'Unable to generate test cases: ' . $e->getMessage(),
                previous: $e
            );
        }

        $testCases = $this->extractTestCases($response->structured ?? []);

        return $this->normalizeTestCases($testCases);
    }

    protected function extractTestCases(mixed $structured): array
    {
        if (!is_array($structured)) {
            return [];
        }

        // Standard case
        if (isset($structured['test_cases']) && is_array($structured['test_cases'])) {
            return array_values(array_filter(
                $structured['test_cases'],
                fn($item) => is_array($item) && $this->isSingleTestCase($item)
            ));
        }

        // AI returns single test case
        if ($this->isSingleTestCase($structured)) {
            return [$structured];
        }

        // AI returns multiple test cases
        return array_values(array_filter(
            $structured,
            fn($item) => is_array($item) && $this->isSingleTestCase($item)
        ));

    }

    protected function isSingleTestCase(array $payload): bool
    {
        return isset($payload['title'], $payload['steps']);
    }

    protected function normalizeTestCases(array $testCases): array
    {
        return array_values(array_map(function (array $testCase): array {
            if (!empty($testCase['priority'])) {
                $priority = trim($testCase['priority']);
                $normalized = strtolower($priority);
                $mapping = [
                    'high' => 'High',
                    'medium' => 'Medium',
                    'low' => 'Low',
                ];

                $testCase['priority'] = $mapping[$normalized] ?? ucfirst($normalized);
            }

            return $testCase;
        }, array_filter($testCases, fn($testCase) => is_array($testCase))));
    }

    protected function buildMessage(string $requirementText, string $language): string
    {
        return trim($requirementText) . "\n\n"
            . "Generate detailed test cases based on the requirement above.\n"
            . "Language of the test cases: {$language}.";
    }
}
?>

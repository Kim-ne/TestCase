<?php

namespace App\Services\Contracts;

interface TestCaseGeneratorServiceInterface
{
    /**
     * Generate test cases.
     *
     * @return array<int, array{title: string, preconditions: string, steps: array<int, string>, expected_result: string, priority: 'High'|'Medium'|'Low'}>
     */
    public function generate(?string $text, ?string $filePath, ?string $extension, string $language): array;
}

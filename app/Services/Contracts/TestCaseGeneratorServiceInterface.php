<?php

namespace App\Services\Contracts;

use App\Models\TestGenerationRequest;

interface TestCaseGeneratorServiceInterface
{
    /**
     * Generate test cases.
     *
     * @return array<int, array{title: string, preconditions: string, steps: array<int, string>, expected_result: string, priority: 'High'|'Medium'|'Low'}>
     */
    public function generate(?string $text, ?string $filePath, ?string $extension, string $language): array;

    /**
     * Create a pending generation request and send it to the queue.
     */
    public function queue(?string $text, ?string $filePath, ?string $extension, string $language): TestGenerationRequest;

    /**
     * Generate and persist test cases for an existing queued request.
     *
     * @return array<int, array{title: string, preconditions: string, steps: array<int, string>, expected_result: string, priority: 'High'|'Medium'|'Low'}>
     */
    public function generateForRequest(TestGenerationRequest $generationRequest): array;
}

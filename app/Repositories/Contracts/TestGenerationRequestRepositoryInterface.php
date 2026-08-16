<?php

namespace App\Repositories\Contracts;

use App\Models\TestGenerationRequest;

interface TestGenerationRequestRepositoryInterface
{
    /**
     * Create a new test generation request, status defaults to 'pending'.
     */
    public function create(array $requestData): TestGenerationRequest;

    /**
     * Mark a test generation request as completed.
     *
     * @param  array<int, array{title: string, preconditions: string, steps: array<int, string>, expected_result: string}>  $testCases
     */
    public function markAsCompleted(TestGenerationRequest $request, array $testCases): TestGenerationRequest;

    /**
     * Mark a test generation request as failed and save the error message.
     */
    public function markAsFailed(TestGenerationRequest $request, string $errorMessage): TestGenerationRequest;
}

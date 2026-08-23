<?php

namespace App\Jobs;

use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Models\TestGenerationRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateTestCasesJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $generationRequestId) {}

    /**
     * Execute the job.
     */
    public function handle(TestCaseGeneratorServiceInterface $generator): void
    {
        $generationRequest = TestGenerationRequest::find($this->generationRequestId);

        if ($generationRequest === null || $generationRequest->status !== 'pending') {
            return;
        }

        try {
            $generator->generateForRequest($generationRequest);
        } catch (InvalidRequirementContentException|TestCaseGenerationFailedException) {
            // The service has already persisted the request as failed.
        }
    }
}

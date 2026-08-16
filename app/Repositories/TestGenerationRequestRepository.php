<?php

namespace App\Repositories;

use App\Repositories\Contracts\TestGenerationRequestRepositoryInterface;
use App\Models\TestGenerationRequest;
use Illuminate\Support\Facades\DB;

class TestGenerationRequestRepository implements TestGenerationRequestRepositoryInterface
{
    public function create(array $requestData): TestGenerationRequest
    {
        $requestData['status'] = 'pending'; // Set the status to 'pending' by default

        return TestGenerationRequest::create($requestData);
    }

    public function markAsCompleted(TestGenerationRequest $request, array $testCases): TestGenerationRequest
    {

        return DB::transaction(function () use ($request, $testCases) {
            $request->update(['status' => 'completed']);

            foreach ($testCases as $testCaseData) {
                $request->testCases()->create($testCaseData);
            }

            return $request;
        });
    }

    public function markAsFailed(TestGenerationRequest $request, string $errorMessage): TestGenerationRequest
    {
        $request->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        return $request;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestCaseRequest;
use App\Models\TestGenerationRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class TestCaseGenerationController extends Controller
{
    public function __construct(
        protected TestCaseGeneratorServiceInterface $generator,
    ) {}

    public function __invoke(StoreTestCaseRequest $request): JsonResponse
    {

        try {
            $file = $request->file('file');
            $storedPath = $file?->store('requirements', 'public');
            $absolutePath = $storedPath ? Storage::disk('public')->path($storedPath) : null;
            $generationRequest = $this->generator->queue(
                $request->input('text'),
                $absolutePath,
                $file?->extension(),
                $request->string('output_language')->toString(),
            );

            return response()->json([
                'data' => [
                    'request_id' => $generationRequest->id,
                    'status' => $generationRequest->status,
                    'status_url' => route('api.test-cases.status', $generationRequest),
                ],
            ], 202);
        } catch (InvalidRequirementContentException) {
            return response()->json([
                'message' => 'The provided requirement could not be processed.',
            ], 422);
        } catch (TestCaseGenerationFailedException) {
            return response()->json([
                'message' => 'Unable to generate test cases at this time.',
            ], 502);
        }
    }

    public function status(TestGenerationRequest $generationRequest): JsonResponse
    {
        $data = [
            'request_id' => $generationRequest->id,
            'status' => $generationRequest->status,
        ];

        if ($generationRequest->status === 'completed') {
            $data['test_cases'] = $generationRequest->testCases->map(fn ($testCase): array => [
                'title' => $testCase->title,
                'preconditions' => $testCase->preconditions,
                'steps' => $testCase->steps,
                'expected_result' => $testCase->expected_result,
                'priority' => $testCase->priority,
            ])->all();
        }

        return response()->json(['data' => $data]);
    }
}

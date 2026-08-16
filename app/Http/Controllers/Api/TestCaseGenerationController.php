<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTestCaseRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Http\JsonResponse;

class TestCaseGenerationController extends Controller
{
    public function __construct(
        protected TestCaseGeneratorServiceInterface $generator,
    ) {}

    public function __invoke(StoreTestCaseRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');

            $testCases = $this->generator->generate(
                $request->input('text'),
                $file?->getRealPath(),
                $file?->extension(),
                $request->string('output_language')->toString(),
            );

            return response()->json([
                'data' => [
                    'test_cases' => $testCases,
                ],
            ]);
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
}

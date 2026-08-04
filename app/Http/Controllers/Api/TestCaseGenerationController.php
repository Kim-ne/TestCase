<?php

namespace App\Http\Controllers\Api;

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
            $testCases = $this->generator->generate(
                $request->input('text'),
                $request->file('file')?->getRealPath(),
                $request->file('file')?->getClientOriginalExtension(),
                $request->input('output_language', 'en'),
            );

            return response()->json([
                'data' => [
                    'test_cases' => $testCases,
                ],
            ]);
        } catch (TestCaseGenerationFailedException) {
            return response()->json([
                'message' => 'Unable to generate test cases at this time.',
            ], 502);
        }
    }
}

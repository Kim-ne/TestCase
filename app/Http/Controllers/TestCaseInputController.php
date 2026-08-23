<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Http\Requests\StoreTestCaseRequest;
use App\Models\TestGenerationRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TestCaseInputController extends Controller
{
    public function __construct(
        protected TestCaseGeneratorServiceInterface $generator,
    ) {}

    public function index(Request $request): View
    {
        $generationRequest = $this->generationRequestFromSession($request);
        $testCases = $generationRequest?->status === 'completed'
            ? $generationRequest->testCases->map(fn ($testCase): array => [
                'title' => $testCase->title,
                'preconditions' => $testCase->preconditions,
                'steps' => $testCase->steps,
                'expected_result' => $testCase->expected_result,
                'priority' => $testCase->priority,
            ])->all()
            : $request->session()->get('test_cases', []);

        return view('test-case-input', compact('generationRequest', 'testCases'));
    }

    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        try {
            $file = $request->file('file');
            $storedPath = $file?->store('requirements', 'public');
            $absolutePath = $storedPath ? Storage::disk('public')->path($storedPath)
                                        : null;

            $generationRequest = $this->generator->queue(
                $request->input('text'),
                $absolutePath,
                $file?->extension(),
                $request->string('output_language')->toString(),
            );

            $request->session()->put('test_generation_request_id', $generationRequest->id);

            return back();
        } catch (InvalidRequirementContentException) {
            return back()
                ->with('error', 'Unable to process the provided requirement.');
        } catch (TestCaseGenerationFailedException) {
            return back()
                ->with('error', 'Unable to generate test cases at this time.');
        }
    }

    public function status(Request $request, TestGenerationRequest $generationRequest): JsonResponse
    {
        abort_unless(
            (int) $request->session()->get('test_generation_request_id') === $generationRequest->id,
            404,
        );

        return response()->json([
            'data' => [
                'request_id' => $generationRequest->id,
                'status' => $generationRequest->status,
            ],
        ]);
    }

    private function generationRequestFromSession(Request $request): ?TestGenerationRequest
    {
        $generationRequestId = $request->session()->get('test_generation_request_id');

        return $generationRequestId
            ? TestGenerationRequest::with('testCases')->find($generationRequestId)
            : null;
    }
}

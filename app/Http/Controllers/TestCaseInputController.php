<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Http\Requests\StoreTestCaseRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Http\RedirectResponse;

class TestCaseInputController extends Controller
{
    public function __construct(
        protected TestCaseGeneratorServiceInterface $generator,
    ) {}

    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        try {
            $file = $request->file('file');

            $result = $this->generator->generate(
                $request->input('text'),
                $file?->store('requirements', 'public'),
                $file?->extension(),
                $request->string('output_language')->toString(),
            );

            return back()->with('test_cases', $result);
        } catch (InvalidRequirementContentException) {
            return back()
                ->with('error', 'Unable to process the provided requirement.');
        } catch (TestCaseGenerationFailedException) {
            return back()
                ->with('error', 'Unable to generate test cases at this time.');
        }
    }
}

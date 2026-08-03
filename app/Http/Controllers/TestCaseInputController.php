<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestCaseRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use App\Exceptions\TestCaseGenerationFailedException;
use Illuminate\Http\RedirectResponse;

class TestCaseInputController extends Controller
{
    public function __construct(
        protected TestCaseGeneratorServiceInterface $generator
    ){}

    public function store(StoreTestCaseRequest $request): RedirectResponse
    {
        try
        {
            $result = $this->generator->generate(
                                $request->input('text'),
                                $request->file('file')?->getRealPath(),
                                $request->file('file')?->getClientOriginalExtension(),
                                $request->input('output_language'),
                            );

            return back()->with('test_cases', $result);
        } catch (TestCaseGenerationFailedException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

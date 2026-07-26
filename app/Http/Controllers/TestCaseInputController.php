<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTestCaseRequest;
use App\Services\FileExtraction\TextNormalizerService;
use Illuminate\Http\RedirectResponse;

class TestCaseInputController extends Controller
{
    public function store(StoreTestCaseRequest $request, TextNormalizerService $normalizer): RedirectResponse
    {
        try
        {
            $plainText = $normalizer->normalize(
            $request->input('text'),
            $request->file('file')?->getRealPath(),
            $request->file('file')?->getClientOriginalExtension()
            );
            // Dùng $plainText để đưa vào prompt / model generation
            return back()->with('plain_text', $plainText);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

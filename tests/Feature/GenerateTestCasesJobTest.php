<?php

namespace Tests\Feature;

use App\Ai\Agents\TestCaseGeneratorAgent;
use App\Jobs\GenerateTestCasesJob;
use App\Models\TestGenerationRequest;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerateTestCasesJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_persists_test_cases_for_a_pending_request(): void
    {
        $generationRequest = TestGenerationRequest::create([
            'input_type' => 'text',
            'input_text' => 'Users can log in with valid credentials.',
            'output_language' => 'en',
            'provider' => 'gemini',
        ]);
        TestCaseGeneratorAgent::fake([
            [
                'test_cases' => [
                    [
                        'title' => 'User can log in',
                        'preconditions' => 'The user has an account',
                        'steps' => ['Enter email and password', 'Submit the form'],
                        'expected_result' => 'The dashboard is displayed',
                        'priority' => 'high',
                    ],
                ],
            ],
        ]);

        (new GenerateTestCasesJob($generationRequest->id))
            ->handle(app(TestCaseGeneratorServiceInterface::class));

        $generationRequest->refresh();

        $this->assertSame('completed', $generationRequest->status);
        $this->assertDatabaseHas('test_cases', [
            'test_generation_request_id' => $generationRequest->id,
            'title' => 'User can log in',
            'priority' => 'High',
        ]);
    }
}

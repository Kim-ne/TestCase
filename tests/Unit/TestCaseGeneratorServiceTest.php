<?php

namespace Tests\Unit;

use App\Ai\Agents\TestCaseGeneratorAgent;
use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Jobs\GenerateTestCasesJob;
use App\Models\TestGenerationRequest;
use App\Repositories\Contracts\TestGenerationRequestRepositoryInterface;
use App\Services\Contracts\FileTextExtractorInterface;
use App\Services\FileExtraction\TextNormalizerService;
use App\Services\TestCaseGeneratorService;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TestCaseGeneratorServiceTest extends TestCase
{
    public function test_it_rejects_empty_requirement_content(): void
    {
        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            new TestCaseGeneratorAgent,
            $this->repositoryThatDoesNotCreateRequests(),
        );

        $this->expectException(InvalidRequirementContentException::class);

        $service->generate(" \n\t ", null, null, 'en');
    }

    public function test_it_rejects_extracted_content_longer_than_the_supported_limit(): void
    {
        $extractor = new class implements FileTextExtractorInterface
        {
            public function supports(string $extension): bool
            {
                return $extension === 'pdf';
            }

            public function extract(string $path): string
            {
                return str_repeat('a', 50001);
            }
        };

        $service = new TestCaseGeneratorService(
            new TextNormalizerService([$extractor]),
            new TestCaseGeneratorAgent,
            $this->repositoryThatDoesNotCreateRequests(),
        );

        $this->expectException(InvalidRequirementContentException::class);

        $service->generate(null, 'document-path', 'pdf', 'en');
    }

    public function test_it_rejects_ai_test_cases_without_usable_steps(): void
    {
        TestCaseGeneratorAgent::fake([
            [
                'test_cases' => [
                    [
                        'title' => 'User can log in',
                        'preconditions' => null,
                        'steps' => ['', null, 123],
                        'expected_result' => 'The dashboard is displayed',
                        'priority' => 'high',
                    ],
                ],
            ],
        ]);

        $generationRequest = $this->pendingTextRequest();
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->andReturn($generationRequest);
        $repository->shouldReceive('markAsFailed')
            ->once()
            ->with($generationRequest, 'Test case generation failed.');

        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            new TestCaseGeneratorAgent,
            $repository,
        );

        $this->expectException(TestCaseGenerationFailedException::class);

        $service->generate('User login requirement', null, null, 'en');
    }

    public function test_it_marks_the_request_as_failed_when_the_ai_provider_throws(): void
    {
        $generationRequest = $this->pendingTextRequest();
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->andReturn($generationRequest);
        $repository->shouldReceive('markAsFailed')
            ->once()
            ->with($generationRequest, 'Test case generation failed.');

        $agent = Mockery::mock(TestCaseGeneratorAgent::class);
        $agent->shouldReceive('prompt')
            ->once()
            ->andThrow(new \RuntimeException('Provider connection failed.'));

        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            $agent,
            $repository,
        );

        $this->expectException(TestCaseGenerationFailedException::class);

        $service->generate('User login requirement', null, null, 'en');
    }

    public function test_it_returns_a_safe_exception_when_the_request_cannot_be_saved(): void
    {
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->andThrow(new \RuntimeException('Database connection failed.'));

        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            new TestCaseGeneratorAgent,
            $repository,
        );

        $this->expectException(TestCaseGenerationFailedException::class);

        $service->generate('User login requirement', null, null, 'en');
    }

    public function test_it_trims_usable_ai_test_case_values(): void
    {
        TestCaseGeneratorAgent::fake([
            [
                'test_cases' => [
                    [
                        'title' => ' User can log in ',
                        'preconditions' => null,
                        'steps' => [' Enter credentials ', ' ', 'Submit the form'],
                        'expected_result' => ' The dashboard is displayed ',
                        'priority' => 'medium',
                    ],
                ],
            ],
        ]);

        $generationRequest = $this->pendingTextRequest();
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $requestData): bool {
                return $requestData['input_type'] === 'text'
                    && $requestData['input_text'] === 'User login requirement'
                    && $requestData['input_file_path'] === null
                    && $requestData['output_language'] === 'en';
            }))
            ->andReturn($generationRequest);
        $repository->shouldReceive('markAsCompleted')
            ->once()
            ->with($generationRequest, Mockery::on(function (array $testCases): bool {
                return $testCases[0]['priority'] === 'Medium'
                    && $testCases[0]['steps'] === ['Enter credentials', 'Submit the form'];
            }));

        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            new TestCaseGeneratorAgent,
            $repository,
        );

        $testCases = $service->generate('User login requirement', null, null, 'en');

        $this->assertSame([
            [
                'title' => 'User can log in',
                'preconditions' => '',
                'steps' => ['Enter credentials', 'Submit the form'],
                'expected_result' => 'The dashboard is displayed',
                'priority' => 'Medium',
            ],
        ], $testCases);
    }

    public function test_it_dispatches_a_job_for_a_pending_generation_request(): void
    {
        Queue::fake();

        $generationRequest = $this->pendingTextRequest(['id' => 123]);
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldReceive('create')
            ->once()
            ->andReturn($generationRequest);

        $service = new TestCaseGeneratorService(
            new TextNormalizerService,
            new TestCaseGeneratorAgent,
            $repository,
        );

        $queuedRequest = $service->queue('User login requirement', null, null, 'en');

        $this->assertSame($generationRequest, $queuedRequest);
        Queue::assertPushed(
            GenerateTestCasesJob::class,
            fn (GenerateTestCasesJob $job): bool => $job->generationRequestId === 123,
        );
    }

    private function repositoryThatDoesNotCreateRequests(): MockInterface
    {
        $repository = Mockery::mock(TestGenerationRequestRepositoryInterface::class);
        $repository->shouldNotReceive('create');

        return $repository;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function pendingTextRequest(array $attributes = []): TestGenerationRequest
    {
        $generationRequest = new TestGenerationRequest([
            'input_type' => 'text',
            'input_text' => 'User login requirement',
            'output_language' => 'en',
            'status' => 'pending',
        ]);
        $generationRequest->forceFill($attributes);

        return $generationRequest;
    }
}

<?php

namespace Tests\Feature;

use App\Ai\Agents\TestCaseGeneratorAgent;
use App\Exceptions\InvalidRequirementContentException;
use App\Exceptions\TestCaseGenerationFailedException;
use App\Models\User;
use App\Services\Contracts\TestCaseGeneratorServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class TestCaseGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_test_cases_from_text_input(): void
    {
        TestCaseGeneratorAgent::fake([
            $this->validAgentResponse('Login succeeds with correct information', 'high'),
        ]);

        $response = $this->post(route('test-case-input'), [
            'text' => 'User login with email and password',
            'output_language' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('test_cases');

        $testCases = session('test_cases');

        $this->assertCount(1, $testCases);
        $this->assertSame('High', $testCases[0]['priority']);
        $this->assertArrayHasKey('preconditions', $testCases[0]);
    }

    public function test_it_displays_generated_test_cases_on_the_input_page(): void
    {
        $response = $this->withSession([
            'test_cases' => $this->validTestCases(),
        ])->get('/');

        $response->assertOk()
            ->assertSee('Test case đã tạo')
            ->assertSee('User can log in')
            ->assertSee('The user has an account')
            ->assertSee('High');
    }

    public function test_it_passes_an_uploaded_file_to_the_generator(): void
    {
        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn ($text, $path, $extension, $language): bool => $text === null
                    && is_string($path)
                    && is_string($extension)
                    && $language === 'en')
                ->andReturn($this->validTestCases());
        });

        $file = UploadedFile::fake()->create('requirements.pdf', 100, 'application/pdf');

        $response = $this->post(route('test-case-input'), [
            'file' => $file,
            'output_language' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('test_cases');
    }

    public function test_it_passes_a_txt_file_to_the_generator(): void
    {
        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn ($text, $path, $extension, $language): bool => $text === null
                    && is_string($path)
                    && $extension === 'txt'
                    && $language === 'en')
                ->andReturn($this->validTestCases());
        });

        $file = UploadedFile::fake()->createWithContent('requirements.txt', 'User can log in.');

        $response = $this->post(route('test-case-input'), [
            'file' => $file,
            'output_language' => 'en',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('test_cases');
    }

    public function test_it_fails_validation_when_neither_text_nor_file_is_provided(): void
    {
        $response = $this->post(route('test-case-input'), [
            'output_language' => 'vi',
        ]);

        $response->assertSessionHasErrors(['text', 'file']);
    }

    public function test_it_requires_an_output_language(): void
    {
        $response = $this->post(route('test-case-input'), [
            'text' => 'Some requirement',
        ]);

        $response->assertSessionHasErrors('output_language');
    }

    public function test_it_fails_validation_with_invalid_output_language(): void
    {
        $response = $this->post(route('test-case-input'), [
            'text' => 'Some requirement',
            'output_language' => 'fr',
        ]);

        $response->assertSessionHasErrors('output_language');
    }

    public function test_it_rejects_text_longer_than_the_supported_limit(): void
    {
        $response = $this->post(route('test-case-input'), [
            'text' => str_repeat('a', 50001),
            'output_language' => 'en',
        ]);

        $response->assertSessionHasErrors('text');
    }

    public function test_web_returns_a_safe_error_when_ai_generation_fails(): void
    {
        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new TestCaseGenerationFailedException('Provider secret detail'));
        });

        $response = $this->post(route('test-case-input'), [
            'text' => 'Some requirement',
            'output_language' => 'en',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Unable to generate test cases at this time.');
        $this->assertStringNotContainsString('secret', session('error'));
    }

    public function test_it_rejects_an_incomplete_ai_response(): void
    {
        TestCaseGeneratorAgent::fake([
            [
                'test_cases' => [
                    [
                        'title' => 'Incomplete test case',
                        'steps' => ['Perform an action'],
                    ],
                ],
            ],
        ]);

        $response = $this->post(route('test-case-input'), [
            'text' => 'Some requirement',
            'output_language' => 'en',
        ]);

        $response->assertRedirect()
            ->assertSessionHas('error', 'Unable to generate test cases at this time.');
    }

    public function test_api_requires_authentication(): void
    {
        $response = $this->postJson(route('api.test-cases.generate'), [
            'text' => 'Users can log in with valid credentials.',
            'output_language' => 'en',
        ]);

        $response->assertUnauthorized();
    }

    public function test_api_generates_test_cases_for_an_authenticated_user(): void
    {
        $this->authenticateApiUser();

        TestCaseGeneratorAgent::fake([
            $this->validAgentResponse('User can log in', 'medium'),
        ]);

        $response = $this->postJson(route('api.test-cases.generate'), [
            'text' => 'Users can log in with valid credentials.',
            'output_language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.test_cases.0.title', 'User can log in')
            ->assertJsonPath('data.test_cases.0.priority', 'Medium');
    }

    public function test_api_accepts_a_txt_file_for_an_authenticated_user(): void
    {
        $this->authenticateApiUser();

        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->withArgs(fn ($text, $path, $extension, $language): bool => $text === null
                    && is_string($path)
                    && $extension === 'txt'
                    && $language === 'en')
                ->andReturn($this->validTestCases());
        });

        $file = UploadedFile::fake()->createWithContent('requirements.txt', 'User can log in.');

        $response = $this->postJson(route('api.test-cases.generate'), [
            'file' => $file,
            'output_language' => 'en',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.test_cases.0.title', 'User can log in');
    }

    public function test_api_returns_json_validation_errors_for_an_authenticated_user(): void
    {
        $this->authenticateApiUser();

        $response = $this->postJson(route('api.test-cases.generate'), [
            'output_language' => 'fr',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['text', 'file', 'output_language']);
    }

    public function test_api_returns_422_for_unreadable_requirement_content(): void
    {
        $this->authenticateApiUser();

        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new InvalidRequirementContentException('Parser detail'));
        });

        $response = $this->postJson(route('api.test-cases.generate'), [
            'text' => 'Some requirement',
            'output_language' => 'en',
        ]);

        $response->assertUnprocessable()
            ->assertExactJson([
                'message' => 'The provided requirement could not be processed.',
            ]);
    }

    public function test_api_returns_502_without_exposing_provider_details(): void
    {
        $this->authenticateApiUser();

        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->once()
                ->andThrow(new TestCaseGenerationFailedException('Provider secret detail'));
        });

        $response = $this->postJson(route('api.test-cases.generate'), [
            'text' => 'Some requirement',
            'output_language' => 'en',
        ]);

        $response->assertStatus(502)
            ->assertExactJson([
                'message' => 'Unable to generate test cases at this time.',
            ]);
        $this->assertStringNotContainsString('secret', $response->getContent());
    }

    public function test_api_rate_limits_expensive_generation_requests(): void
    {
        $this->authenticateApiUser();

        $this->mock(TestCaseGeneratorServiceInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('generate')
                ->times(10)
                ->andReturn($this->validTestCases());
        });

        for ($requestNumber = 1; $requestNumber <= 10; $requestNumber++) {
            $this->postJson(route('api.test-cases.generate'), [
                'text' => 'Some requirement',
                'output_language' => 'en',
            ])->assertOk();
        }

        $this->postJson(route('api.test-cases.generate'), [
            'text' => 'Some requirement',
            'output_language' => 'en',
        ])->assertTooManyRequests();
    }

    private function authenticateApiUser(): void
    {
        Sanctum::actingAs(new User, ['*']);
    }

    private function validAgentResponse(string $title, string $priority): array
    {
        return [
            'test_cases' => [
                [
                    'title' => $title,
                    'preconditions' => 'The user has an account',
                    'steps' => ['Enter email and password', 'Submit the form'],
                    'expected_result' => 'The dashboard is displayed',
                    'priority' => $priority,
                ],
            ],
        ];
    }

    private function validTestCases(): array
    {
        return $this->validAgentResponse('User can log in', 'High')['test_cases'];
    }
}

<?php

namespace Tests\Feature;

use App\Ai\Agents\TestCaseGeneratorAgent;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TestCaseGenerationTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);
    }
    public function test_it_generates_test_cases_from_text_input(): void
    {
        TestCaseGeneratorAgent::fake([
            [
                'test_cases' => [
                    [
                        'title' => 'login successed with correct information',
                        'preconditions' => 'user is existed',
                        'steps' => ['fill email', 'fill password', 'click login button'],
                        'expected_result' => 'success',
                        'priority' => 'high'
                    ],
                ],
            ]
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
    }

    public function test_it_generates_test_cases_from_pdf_file(): void
    {
        TestCaseGeneratorAgent::fake([
            ['test_cases' => [] ],
        ]);

        $file = UploadedFile::fake()->create('test.pdf', 100, 'application/pdf');

        $response = $this->post(route('test-case-input'), [
            'file' => $file,
            'output_language' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('test_cases');
    }

    public function test_it_fails_validation_when_neither_text_nor_file_provided(): void
    {
        $response = $this->post(route('test-case-input'), [
            'output_language' => 'vi',
        ]);

        $response->assertSessionHasErrors(['text', 'file']);
    }

    public function test_it_fails_validation_with_invalid_output_language(): void
    {
        $response = $this->post(route('test-case-input'), [
            'text' => 'Some requirement',
            'output_language' => 'fr', // not in supported languages
        ]);

        $response->assertSessionHasErrors('output_language');
    }

}

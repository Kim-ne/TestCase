<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_test_case_input_page_is_displayed(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('test-case-input')
            ->assertSee('Test Case Generator')
            ->assertSee('Tạo test case');
    }
}

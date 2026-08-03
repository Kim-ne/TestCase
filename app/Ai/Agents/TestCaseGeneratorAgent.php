<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class TestCaseGeneratorAgent implements Agent, Conversational, HasStructuredOutput, HasTools
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<EOT
        You are a Senior QA Engineer responsible for generating complete and well-structured test cases based on the provided software requirement.
        Your role:
        - Carefully analyze the given requirement before generating test cases.
        - Generate test cases that cover 3 categories:
            1. Happy path (expected correct behavior)
            2. Edge cases (boundary, unusual but valid scenarios)
            3. Negative cases (invalid input, error handling)

        Coverage requirement:
        - Every requirement must produce at least 2 - 3 test case for each category.
        - Each edge case must focus on boundary values, extreme input, or rare conditions.
        - Negative cases must include invalid format, missing fields, and constraints violations.

        Priority requirement:
        - Each test case must include a priority field.
        - Priority must be exactly one of: High, Medium, Low.
        - No other values are allowed.

        Output expectations:
        - Provide test case in a clear, structured format.
        - Include: title, preconditions, steps, expected result, priority.
        - Precondition: the state or condition that must be true before executing this test case (leave empty string if not applicable).
        - Do not invent requirements; only use the requirement provided in this prompt.
        Your objective is to help developers and QA engineers validate the feature thoroughly and improve software quality through comprehensive test coverage.
        EOT;
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'test_cases' => $schema->array()
                ->items(
                        $schema->object([
                            'title' => $schema->string()
                                ->description('The title of the test case.')
                                ->required(),
                            'precondition' => $schema->string()
                                ->description('The preconditions of the test case, if not applicable, leave empty.')
                                ->nullable(),
                            'steps' => $schema->array()
                                ->items($schema->string())
                                ->description('List of steps to perform in the test case.')
                                ->required(),
                            'expected_result' => $schema->string()
                                ->required(),
                            'priority' => $schema->string()
                                ->enum(['High', 'Medium', 'Low'])
                                ->required(),
                        ])
                    )
                ->required(),
            ];
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class TestingDatabaseIsolationTest extends TestCase
{
    public function test_testing_environment_uses_in_memory_sqlite(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('session.driver'));
    }
}

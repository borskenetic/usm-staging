<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

class TestingDatabaseConnectionTest extends TestCase
{
    public function test_phpunit_forces_the_memory_database(): void
    {
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
    }
}

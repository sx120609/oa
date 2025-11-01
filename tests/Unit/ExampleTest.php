<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function test_generates_unique_identifier(): void
    {
        $identifier = uniqid('', true);

        $this->assertGreaterThan(0, strlen($identifier));
    }
}

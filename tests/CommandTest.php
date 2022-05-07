<?php
declare(strict_types=1);

namespace Tests;

use
    PHPUnit\Framework\TestCase,
    Tests\Mock\Arguments,
    Tests\Mock\Test;

final class CommandTest extends TestCase
{

    public function testGetDescription(): void
    {
        $this->assertSame(
            'This is a test command.',
            (new Test)->getDescription()
        );
    }

    public function testGetName(): void
    {
        $this->assertSame(
            'Test Command',
            (new Test)->getName()
        );
    }

    public function testGetNameDefault(): void
    {
        $this->assertSame(
            'Arguments',
            (new Arguments)->getName()
        );
    }

}

<?php
declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Tests\Mock\ArgumentsCommand;
use Tests\Mock\TestCommand;

final class CommandTest extends TestCase
{

    public function testGetDescription(): void
    {
        $this->assertSame(
            'This is a test command.',
            (new TestCommand)->getDescription()
        );
    }

    public function testGetName(): void
    {
        $this->assertSame(
            'Test Command',
            (new TestCommand)->getName()
        );
    }

    public function testGetNameDefault(): void
    {
        $this->assertSame(
            'Arguments',
            (new ArgumentsCommand)->getName()
        );
    }

}

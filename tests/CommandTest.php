<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Console\Console;
use Fyre\Container\Container;
use Fyre\Loader\Loader;
use PHPUnit\Framework\TestCase;
use Tests\Mock\ArgumentsCommand;
use Tests\Mock\TestCommand;

final class CommandTest extends TestCase
{
    protected Container $container;

    public function testGetAlias(): void
    {
        $this->assertSame(
            'tester',
            $this->container->build(TestCommand::class)->getAlias()
        );
    }

    public function testGetAliasDefault(): void
    {
        $this->assertSame(
            'arguments',
            $this->container->build(ArgumentsCommand::class)->getAlias()
        );
    }

    public function testGetDescription(): void
    {
        $this->assertSame(
            'This is a test command.',
            $this->container->build(TestCommand::class)->getDescription()
        );
    }

    public function testGetName(): void
    {
        $this->assertSame(
            'Test Command',
            $this->container->build(TestCommand::class)->getName()
        );
    }

    public function testGetNameDefault(): void
    {
        $this->assertSame(
            'Arguments',
            $this->container->build(ArgumentsCommand::class)->getName()
        );
    }

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->container->singleton(Console::class);
    }
}

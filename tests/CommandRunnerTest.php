<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Command\Command;
use Fyre\Command\CommandRunner;
use Fyre\Console\Console;
use Fyre\Container\Container;
use Fyre\Loader\Loader;
use PHPUnit\Framework\TestCase;

use function array_keys;

final class CommandRunnerTest extends TestCase
{
    protected CommandRunner $runner;

    public function testAll(): void
    {
        $commands = $this->runner->all();

        $this->assertSame(
            [
                'arguments',
                'booloptions',
                'options',
                'tester',
            ],
            array_keys($commands)
        );

        foreach ($commands as $command) {
            $this->assertInstanceOf(Command::class, $command);
        }
    }

    public function testGetNamepaces(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\\',
            ],
            $this->runner->getNamespaces()
        );
    }

    public function testHandleCommand(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'tester'])
        );
    }

    public function testHandleCommandArgumentBoolMultipleOpts(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'booloptions', '--test', '--other', 'value'])
        );
    }

    public function testHandleCommandArgumentBoolOpts(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'booloptions', '--test'])
        );
    }

    public function testHandleCommandArgumentOpts(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'options', '--test', 'value'])
        );
    }

    public function testHandleCommandArguments(): void
    {
        $this->assertSame(
            0,
            $this->runner->handle(['', 'arguments', 'value'])
        );
    }

    public function testHasCommand(): void
    {
        $this->assertTrue(
            $this->runner->hasCommand('tester')
        );
    }

    public function testHasCommandInvalid(): void
    {
        $this->assertFalse(
            $this->runner->hasCommand('invalid')
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            $this->runner->hasNamespace('Tests\Mock')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            $this->runner->hasNamespace('Tests\Invalid')
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertSame(
            $this->runner,
            $this->runner->removeNamespace('Tests\Mock')
        );

        $this->assertFalse(
            $this->runner->hasNamespace('Tests\Mock')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertSame(
            $this->runner,
            $this->runner->removeNamespace('Tests\Invalid')
        );
    }

    public function testRun(): void
    {
        $this->assertSame(
            0,
            $this->runner->run('tester')
        );
    }

    public function testRunArguments(): void
    {
        $this->assertSame(
            0,
            $this->runner->run('arguments', ['value'])
        );
    }

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(Loader::class);
        $container->singleton(Console::class);

        $container->use(Loader::class)->addNamespaces([
            'Tests' => 'tests',
        ]);

        $this->runner = $container->build(CommandRunner::class, [
            'namespaces' => [
                'Tests\Mock',
            ],
        ]);
    }
}

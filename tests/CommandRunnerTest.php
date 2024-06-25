<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Command\Command;
use Fyre\Command\CommandRunner;
use Fyre\Loader\Loader;
use PHPUnit\Framework\TestCase;

use function array_keys;

final class CommandRunnerTest extends TestCase
{
    public function testAll(): void
    {
        $commands = CommandRunner::all();

        $this->assertSame(
            [
                'arguments',
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
            CommandRunner::getNamespaces()
        );
    }

    public function testHandleCommand(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'tester'])
        );
    }

    public function testHandleCommandArgumentOpts(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'options', '--test', 'value'])
        );
    }

    public function testHandleCommandArguments(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'arguments', 'value'])
        );
    }

    public function testHasCommand(): void
    {
        $this->assertTrue(
            CommandRunner::hasCommand('tester')
        );
    }

    public function testHasCommandInvalid(): void
    {
        $this->assertFalse(
            CommandRunner::hasCommand('invalid')
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            CommandRunner::hasNamespace('Tests\Mock')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            CommandRunner::hasNamespace('Tests\Invalid')
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertTrue(
            CommandRunner::removeNamespace('Tests\Mock')
        );

        $this->assertFalse(
            CommandRunner::hasNamespace('Tests\Mock')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertFalse(
            CommandRunner::removeNamespace('Tests\Invalid')
        );
    }

    public function testRun(): void
    {
        $this->assertSame(
            0,
            CommandRunner::run('tester')
        );
    }

    public function testRunArguments(): void
    {
        $this->assertSame(
            0,
            CommandRunner::run('arguments', ['value'])
        );
    }

    protected function setUp(): void
    {
        Loader::clear();
        CommandRunner::clear();

        Loader::addNamespaces([
            'Tests' => 'tests',
        ]);
        CommandRunner::addNamespace('Tests\Mock');
    }
}

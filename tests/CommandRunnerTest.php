<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Command\CommandRunner;
use Fyre\Loader\Loader;
use PHPUnit\Framework\TestCase;

final class CommandRunnerTest extends TestCase
{

    public function testAll(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\\' => [
                    'ArgumentsCommand' => [
                        'name' => 'Arguments',
                        'description' => ''
                    ],
                    'Deep\AnotherCommand' => [
                        'name' => 'Another',
                        'description' => ''
                    ],
                    'OptionsCommand' => [
                        'name' => 'Options',
                        'description' => ''
                    ],
                    'TestCommand' => [
                        'name' => 'Test Command',
                        'description' => 'This is a test command.'
                    ]
                ]
            ],
            CommandRunner::all()
        );
    }

    public function testGetNamepaces(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\\'
            ],
            CommandRunner::getNamespaces()
        );
    }

    public function testHandleCommand(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'test'])
        );
    }

    public function testHandleCommandArguments(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'arguments', 'value'])
        );
    }

    public function testHandleCommandArgumentOpts(): void
    {
        $this->assertSame(
            0,
            CommandRunner::handle(['', 'options', '--test', 'value'])
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            CommandRunner::hasNamespace('Tests\Mock')
        );
    }

    public function testHasInvalid(): void
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
            CommandRunner::run('test')
        );
    }

    public function testRunDeep(): void
    {
        $this->assertSame(
            1,
            CommandRunner::run('deep/another')
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
            'Tests' => 'tests'
        ]);
        CommandRunner::addNamespace('Tests\Mock');
    }

}

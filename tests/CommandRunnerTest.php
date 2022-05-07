<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\Command\CommandRunner,
    Fyre\Loader\Loader,
    PHPUnit\Framework\TestCase;

final class CommandRunnerTest extends TestCase
{

    public function testAll(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\\' => [
                    'Arguments' => [
                        'name' => 'Arguments',
                        'description' => ''
                    ],
                    'Deep\AnotherCommand' => [
                        'name' => 'AnotherCommand',
                        'description' => ''
                    ],
                    'Test' => [
                        'name' => 'Test Command',
                        'description' => 'This is a test command.'
                    ]
                ]
            ],
            CommandRunner::all()
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
            CommandRunner::run('deep/another_command')
        );
    }

    public function testRunArguments(): void
    {
        $this->assertSame(
            0,
            CommandRunner::run('arguments', ['value'])
        );
    }

    public function testRunInvalid(): void
    {
        $this->assertSame(
            1,
            CommandRunner::run('invalid')
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

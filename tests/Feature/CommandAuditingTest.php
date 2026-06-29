<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

final class CommandAuditingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('recordkeeper.commands.enabled', true);
    }

    public function test_command_finished_creates_audit(): void
    {
        $this->fireCommand('cache:clear', 0);

        $audit = Audit::where('event', 'command.finished')->first();

        $this->assertNotNull($audit);
        $this->assertSame('command', $audit->auditable_type);
        $this->assertSame('cache:clear', $audit->context['command']);
        $this->assertSame(0, $audit->context['exit_code']);
    }

    public function test_command_duration_is_recorded(): void
    {
        $this->fireCommand('cache:clear', 0);

        $audit = Audit::where('event', 'command.finished')->first();

        $this->assertArrayHasKey('duration_ms', $audit->context);
        $this->assertIsInt($audit->context['duration_ms']);
    }

    public function test_excluded_command_is_not_audited(): void
    {
        $this->fireCommand('schedule:run', 0);

        $this->assertSame(0, Audit::where('event', 'command.finished')->count());
    }

    public function test_custom_excluded_command_is_not_audited(): void
    {
        config(['recordkeeper.commands.exclude' => ['cache:clear']]);

        $this->fireCommand('cache:clear', 0);

        $this->assertSame(0, Audit::where('event', 'command.finished')->count());
    }

    public function test_command_not_audited_when_disabled(): void
    {
        config(['recordkeeper.commands.enabled' => false]);

        $this->fireCommand('cache:clear', 0);

        $this->assertSame(0, Audit::where('event', 'command.finished')->count());
    }

    public function test_model_scope_command_audits(): void
    {
        $this->fireCommand('cache:clear', 0);
        Audit::create(['event' => 'updated', 'auditable_type' => 'system', 'old_values' => [], 'new_values' => []]);

        $this->assertSame(1, Audit::commandAudits()->count());
    }

    private function fireCommand(string $command, int $exitCode): void
    {
        $input  = new StringInput('');
        $output = new NullOutput();

        event(new CommandStarting($command, $input, $output));
        event(new CommandFinished($command, $input, $output, $exitCode));
    }
}

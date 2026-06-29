<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use LaraArabDev\Recordkeeper\Attributes\AuditCommand;
use LaraArabDev\Recordkeeper\Models\Audit;

final class RecordCommandAudit
{
    /** @var array<string, float> */
    private static array $startTimes = [];

    /** @return array<string, string> */
    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            CommandStarting::class => 'onStarting',
            CommandFinished::class => 'onFinished',
        ];
    }

    /** @param  CommandStarting  $event */
    public function onStarting(CommandStarting $event): void
    {
        if (! $this->shouldAudit($event->command)) {
            return;
        }

        self::$startTimes[$event->command] = microtime(true);
    }

    /** @param  CommandFinished  $event */
    public function onFinished(CommandFinished $event): void
    {
        if (! $this->shouldAudit($event->command)) {
            return;
        }

        $duration = isset(self::$startTimes[$event->command])
            ? (int) ((microtime(true) - self::$startTimes[$event->command]) * 1000)
            : null;

        unset(self::$startTimes[$event->command]);

        $commandClass = $this->resolveCommandClass($event->command);
        $attr         = $commandClass ? $this->attribute($commandClass) : null;

        $audit = new Audit();
        $audit->fill([
            'event'          => 'command.finished',
            'auditable_type' => 'command',
            'auditable_id'   => null,
            'old_values'     => [],
            'new_values'     => [],
            'user_type'      => null,
            'user_id'        => null,
            'tags'           => implode(',', $attr?->tags ?? []),
            'context'        => array_filter([
                'command'     => $event->command,
                'exit_code'   => $event->exitCode,
                'duration_ms' => $duration,
            ], fn ($v) => $v !== null),
        ]);
        $audit->save();
    }

    /**
     * @param  ?string  $command
     * @return bool
     */
    private function shouldAudit(?string $command): bool
    {
        if (! config('recordkeeper.enabled', true)) {
            return false;
        }

        if ($command === null) {
            return false;
        }

        $excluded = config('recordkeeper.commands.exclude', []);
        if (in_array($command, $excluded, true)) {
            return false;
        }

        if (config('recordkeeper.commands.enabled', false)) {
            return true;
        }

        $commandClass = $this->resolveCommandClass($command);

        return $commandClass && $this->attribute($commandClass) !== null;
    }

    /**
     * @param  string  $commandName
     * @return ?string
     */
    private function resolveCommandClass(string $commandName): ?string
    {
        $artisan = app(\Illuminate\Contracts\Console\Kernel::class);

        if (! method_exists($artisan, 'all')) {
            return null;
        }

        $commands = $artisan->all();

        foreach ($commands as $name => $command) {
            if ($name === $commandName) {
                return $command::class;
            }
        }

        return null;
    }

    /**
     * @param  string  $commandClass
     * @return ?AuditCommand
     */
    private function attribute(string $commandClass): ?AuditCommand
    {
        $attrs = (new \ReflectionClass($commandClass))->getAttributes(AuditCommand::class);

        return $attrs ? $attrs[0]->newInstance() : null;
    }
}

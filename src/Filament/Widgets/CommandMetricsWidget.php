<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use LaraArabDev\Recordkeeper\Models\Audit;

class CommandMetricsWidget extends ChartWidget
{
    protected static ?string $heading = 'Command Performance';

    protected static ?int $sort = 3;

    public ?string $filter = null;

    public static function canView(): bool
    {
        return config('recordkeeper.commands.metrics.memory', true)
            || config('recordkeeper.commands.metrics.audit_count', true);
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /** @return array<string, mixed> */
    protected function getFilters(): ?array
    {
        $commands = Audit::commandAudits()
            ->where('event', 'command.finished')
            ->select('context')
            ->latest()
            ->limit(200)
            ->get()
            ->map(fn ($a) => $a->context['command'] ?? null)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        if (empty($commands)) {
            return null;
        }

        $result = [];
        foreach ($commands as $cmd) {
            $result[$cmd] = $cmd;
        }

        if ($this->filter === null) {
            $this->filter = array_key_first($result);
        }

        return $result;
    }

    /** @return array<string, mixed> */
    protected function getData(): array
    {
        $commandName = $this->filter;

        if ($commandName === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $runs = Audit::commandAudits()
            ->where('event', 'command.finished')
            ->whereRaw("JSON_EXTRACT(context, '$.command') = ?", [$commandName])
            ->latest()
            ->limit(14)
            ->get()
            ->reverse()
            ->values();

        $labels = [];
        $durations = [];
        $auditCounts = [];
        $colors = [];

        foreach ($runs as $run) {
            $labels[] = $run->created_at?->format('m/d H:i') ?? '?';
            $durations[] = $run->context['duration_ms'] ?? 0;
            $auditCounts[] = $run->context['audit_count'] ?? 0;
            $colors[] = ! empty($run->context['anomaly']) ? 'rgba(239,68,68,0.7)' : 'rgba(99,102,241,0.7)';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Duration (ms)',
                    'data' => $durations,
                    'backgroundColor' => $colors,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Audit Impact',
                    'data' => $auditCounts,
                    'backgroundColor' => 'rgba(16,185,129,0.5)',
                    'yAxisID' => 'y1',
                    'type' => 'line',
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['position' => 'left', 'title' => ['display' => true, 'text' => 'Duration (ms)']],
                'y1' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => 'Audit Impact']],
            ],
        ];
    }
}

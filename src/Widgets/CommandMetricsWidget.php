<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\ChartWidget;
use LaraArabDev\Recordkeeper\Models\Audit;

/** Dashboard chart widget visualising command duration and audit impact across recent runs. */
class CommandMetricsWidget extends ChartWidget
{
    /** @var string|null Widget heading shown above the chart. */
    protected static ?string $heading = 'Command Performance';

    /** @var int|null Widget sort order on the dashboard. */
    protected static ?int $sort = 3;

    /** @var string|null Currently selected command name filter value. */
    public ?string $filter = null;

    /**
     * Show this widget only when at least one command metrics config option is enabled.
     */
    public static function canView(): bool
    {
        return config('recordkeeper.commands.metrics.memory', true)
            || config('recordkeeper.commands.metrics.audit_count', true);
    }

    /**
     * Return the Chart.js chart type identifier.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Return a command-name keyed array for the filter dropdown, or null when no commands exist.
     *
     * @return array<string, string>|null
     */
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

        $result = array_combine($commands, $commands);

        if ($this->filter === null) {
            $this->filter = array_key_first($result);
        }

        return $result;
    }

    /**
     * Return the Chart.js dataset structure for the selected command's recent runs.
     *
     * @return array<string, mixed>
     */
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

    /**
     * Return the Chart.js options configuring dual Y-axes for duration and audit count.
     *
     * @return array<string, mixed>
     */
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

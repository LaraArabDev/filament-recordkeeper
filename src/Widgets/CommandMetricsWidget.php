<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Widgets;

use Filament\Widgets\ChartWidget;
use LaraArabDev\Recordkeeper\Models\Audit;

/**
 * Dashboard chart widget showing command execution duration and audit impact over time.
 */
class CommandMetricsWidget extends ChartWidget
{
    protected ?string $heading = 'Command Performance';

    protected static ?int $sort = 3;

    public ?string $filter = null;

    /**
     * Determine whether this widget should be visible based on config.
     */
    public static function canView(): bool
    {
        return config('recordkeeper.commands.metrics.memory', true)
            || config('recordkeeper.commands.metrics.audit_count', true);
    }

    /**
     * Get the chart type.
     */
    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Get the filter options (distinct command names) and auto-select the first.
     *
     * @return array<string, string>|null Null when no command audits exist.
     */
    protected function getFilters(): ?array
    {
        $commands = Audit::commandAudits()
            ->where('event', 'command.finished')
            ->whereNotNull('source')
            ->distinct()
            ->orderBy('source')
            ->pluck('source', 'source')
            ->all();

        if (empty($commands)) {
            return null;
        }

        if ($this->filter === null) {
            $this->filter = array_key_first($commands);
        }

        return $commands;
    }

    /**
     * Get the chart datasets (duration bars and audit impact line) for the selected command.
     *
     * @return array{labels: list<string>, datasets: list<array<string, mixed>>}
     */
    protected function getData(): array
    {
        $commandName = $this->filter;

        if ($commandName === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $runs = Audit::commandAudits()
            ->where('event', 'command.finished')
            ->where('source', $commandName)
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
     * Get the Chart.js options for dual Y-axis configuration.
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

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
    public function getHeading(): ?string
    {
        return __('filament-recordkeeper::audit.widgets.command_performance');
    }

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

    /** @return array<string, string>|null */
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

    /** @return array{labels: list<string>, datasets: list<array<string, mixed>>} */
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
            $ctx = $run->context ?? [];
            $labels[] = $run->created_at?->format('m/d H:i') ?? '?';
            $durations[] = $ctx['duration_ms'] ?? 0;
            $auditCounts[] = $ctx['audit_count'] ?? 0;
            $colors[] = ! empty($ctx['anomaly']) ? 'rgba(239,68,68,0.7)' : 'rgba(99,102,241,0.7)';
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => __('filament-recordkeeper::audit.widgets.duration_ms'),
                    'data' => $durations,
                    'backgroundColor' => $colors,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => __('filament-recordkeeper::audit.widgets.audit_impact'),
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
                'y' => ['position' => 'left', 'title' => ['display' => true, 'text' => __('filament-recordkeeper::audit.widgets.duration_ms')]],
                'y1' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false], 'title' => ['display' => true, 'text' => __('filament-recordkeeper::audit.widgets.audit_impact')]],
            ],
        ];
    }
}

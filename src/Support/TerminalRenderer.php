<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Support;

use LaraArabDev\Recordkeeper\Models\Audit;

final class TerminalRenderer
{
    /**
     * @param  array  $headers
     * @param  array  $rows
     * @return void
     */
    public static function table(array $headers, array $rows): void
    {
        if (empty($rows)) {
            echo "No results found.\n";

            return;
        }

        $widths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $cell) {
                $widths[$i] = max($widths[$i] ?? 0, strlen((string) $cell));
            }
        }

        $line = '+' . implode('+', array_map(fn (int $w) => str_repeat('-', $w + 2), $widths)) . '+';
        echo $line . "\n";

        $headerRow = '|';
        foreach (array_values($headers) as $i => $h) {
            $headerRow .= ' ' . str_pad((string) $h, $widths[$i]) . ' |';
        }
        echo $headerRow . "\n" . $line . "\n";

        foreach ($rows as $row) {
            $dataRow = '|';
            foreach (array_values($row) as $i => $cell) {
                $dataRow .= ' ' . str_pad((string) $cell, $widths[$i]) . ' |';
            }
            echo $dataRow . "\n";
        }

        echo $line . "\n";
    }

    /**
     * @param  Audit  $audit
     * @return void
     */
    public static function diff(Audit $audit): void
    {
        $modified = $audit->getModified();

        if (empty($modified)) {
            echo "  (no attribute changes)\n";

            return;
        }

        foreach ($modified as $attribute => $change) {
            echo "  \033[33m{$attribute}\033[0m\n";
            $old = $change['old'] ?? null;
            $new = $change['new'] ?? null;
            echo "  \033[31m- " . static::formatValue($old) . "\033[0m\n";
            echo "  \033[32m+ " . static::formatValue($new) . "\033[0m\n";
        }
    }

    /**
     * @param  mixed  $data
     * @return void
     */
    public static function json(mixed $data): void
    {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    }

    /**
     * @param  array  $rows
     * @return void
     */
    public static function ndjson(array $rows): void
    {
        foreach ($rows as $row) {
            echo json_encode($row) . "\n";
        }
    }

    /**
     * @param  array  $headers
     * @param  array  $rows
     * @return void
     */
    public static function csv(array $headers, array $rows): void
    {
        $out = fopen('php://output', 'w');
        if ($out === false) {
            return;
        }
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
    }

    /**
     * @param  Audit  $audit
     * @return array
     */
    public static function auditToRow(Audit $audit): array
    {
        return [
            'id'      => $audit->id,
            'event'   => $audit->event,
            'subject' => class_basename((string) $audit->auditable_type) . ' #' . $audit->auditable_id,
            'actor'   => $audit->user_id
                ? (class_basename((string) ($audit->user_type ?? 'User')) . ' #' . $audit->user_id)
                : 'system',
            'changed' => implode(', ', array_keys($audit->getModified() ?? [])),
            'batch'   => $audit->batch_id ?? '',
            'created' => $audit->created_at?->diffForHumans() ?? '',
        ];
    }

    /**
     * @param  mixed  $value
     * @return string
     */
    private static function formatValue(mixed $value): string
    {
        if ($value === null) {
            return '(null)';
        }
        if ($value === '***' || (is_string($value) && str_starts_with($value, '__encrypted:'))) {
            return '*** (redacted/encrypted)';
        }
        if (is_array($value)) {
            return json_encode($value);
        }

        return (string) $value;
    }
}

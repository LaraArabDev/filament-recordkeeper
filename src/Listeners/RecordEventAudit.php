<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Listeners;

use LaraArabDev\Recordkeeper\Attributes\AuditEvent;
use LaraArabDev\Recordkeeper\Models\Audit;

final class RecordEventAudit
{
    public function handle(string $eventName, array $payload): void
    {
        if (! config('recordkeeper.enabled', true)) {
            return;
        }

        if (str_starts_with($eventName, 'Illuminate\\')
            || str_starts_with($eventName, 'Laravel\\')
            || str_starts_with($eventName, 'eloquent.')
        ) {
            return;
        }

        $eventClass = $eventName;

        $attr = $this->attribute($eventClass);
        $inConfig = in_array($eventClass, config('recordkeeper.listen', []), true);

        if ($attr === null && ! $inConfig) {
            return;
        }

        $context = ['event' => $eventClass];

        if ($attr?->capturePayload) {
            $context['payload'] = $this->serializePayload($payload);
        }

        $audit = new Audit;
        $audit->fill([
            'event' => 'event.'.class_basename($eventClass),
            'auditable_type' => 'event',
            'auditable_id' => null,
            'old_values' => [],
            'new_values' => [],
            'user_type' => null,
            'user_id' => null,
            'tags' => implode(',', $attr?->tags ?? []),
            'context' => $context,
        ]);
        $audit->save();
    }

    private function attribute(string $eventClass): ?AuditEvent
    {
        if (! class_exists($eventClass)) {
            return null;
        }

        $attrs = (new \ReflectionClass($eventClass))->getAttributes(AuditEvent::class);

        return $attrs ? $attrs[0]->newInstance() : null;
    }

    private function serializePayload(array $payload): array
    {
        $result = [];

        foreach ($payload as $item) {
            if (is_object($item)) {
                if (method_exists($item, 'toArray')) {
                    $result[] = $item->toArray();
                } else {
                    $result[] = get_object_vars($item);
                }
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }
}

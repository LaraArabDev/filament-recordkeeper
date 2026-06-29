<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use LaraArabDev\Recordkeeper\Attributes\AuditJob;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\HttpTracker;

final class RecordJobAudit
{
    /** @return array<string, string> */
    public function subscribe(\Illuminate\Events\Dispatcher $events): array
    {
        return [
            JobQueued::class     => 'onQueued',
            JobProcessing::class => 'onProcessing',
            JobProcessed::class  => 'onProcessed',
            JobFailed::class     => 'onFailed',
        ];
    }

    /** @param  JobQueued  $event */
    public function onQueued(JobQueued $event): void
    {
        $jobClass = is_object($event->job) ? $event->job::class : (string) $event->job;
        $attr     = $this->attribute($jobClass);

        if (! $this->shouldAudit($jobClass, $attr) || ($attr && ! $attr->queued)) {
            return;
        }

        $this->write('job.queued', $jobClass, [
            'job'        => $jobClass,
            'connection' => $event->connectionName,
            'queue'      => $event->queue ?? 'default',
        ], $attr?->tags ?? []);
    }

    /** @param  JobProcessing  $event */
    public function onProcessing(JobProcessing $event): void
    {
        $jobClass = $this->resolveJobClass($event->job);
        $attr     = $this->attribute($jobClass);

        if (! $this->shouldAudit($jobClass, $attr) || ($attr && ! $attr->processing)) {
            return;
        }

        $audit = $this->write('job.processing', $jobClass, [
            'job'        => $jobClass,
            'connection' => $event->connectionName,
            'queue'      => $event->job->getQueue(),
            'attempts'   => $event->job->attempts(),
        ], $attr?->tags ?? []);

        if ($audit !== null && config('recordkeeper.http.enabled', false)) {
            app(HttpTracker::class)->setContext($audit->id);
        }
    }

    /** @param  JobProcessed  $event */
    public function onProcessed(JobProcessed $event): void
    {
        $jobClass = $this->resolveJobClass($event->job);
        $attr     = $this->attribute($jobClass);

        if (config('recordkeeper.http.enabled', false)) {
            app(HttpTracker::class)->clearContext();
        }

        if (! $this->shouldAudit($jobClass, $attr) || ($attr && ! $attr->processed)) {
            return;
        }

        $this->write('job.processed', $jobClass, [
            'job'        => $jobClass,
            'connection' => $event->connectionName,
            'queue'      => $event->job->getQueue(),
            'attempts'   => $event->job->attempts(),
        ], $attr?->tags ?? []);
    }

    /** @param  JobFailed  $event */
    public function onFailed(JobFailed $event): void
    {
        $jobClass = $this->resolveJobClass($event->job);
        $attr     = $this->attribute($jobClass);

        if (config('recordkeeper.http.enabled', false)) {
            app(HttpTracker::class)->clearContext();
        }

        if (! $this->shouldAudit($jobClass, $attr) || ($attr && ! $attr->failed)) {
            return;
        }

        $this->write('job.failed', $jobClass, [
            'job'        => $jobClass,
            'connection' => $event->connectionName,
            'queue'      => $event->job->getQueue(),
            'attempts'   => $event->job->attempts(),
            'exception'  => $event->exception->getMessage(),
        ], $attr?->tags ?? []);
    }

    /** @param  string      $jobClass
     *  @param  ?AuditJob   $attr
     *  @return bool
     */
    private function shouldAudit(string $jobClass, ?AuditJob $attr): bool
    {
        if (! config('recordkeeper.enabled', true)) {
            return false;
        }

        $excluded = config('recordkeeper.jobs.exclude', []);
        if (in_array($jobClass, $excluded, true)) {
            return false;
        }

        return config('recordkeeper.jobs.enabled', false) || $attr !== null;
    }

    /**
     * @param  string  $eventName
     * @param  string  $jobClass
     * @param  array   $context
     * @param  array   $tags
     * @return ?Audit
     */
    private function write(string $eventName, string $jobClass, array $context, array $tags): ?Audit
    {
        $audit = new Audit();
        $audit->fill([
            'event'          => $eventName,
            'auditable_type' => 'job',
            'auditable_id'   => null,
            'old_values'     => [],
            'new_values'     => [],
            'user_type'      => null,
            'user_id'        => null,
            'tags'           => implode(',', $tags),
            'context'        => $context,
        ]);
        $audit->save();

        return $audit;
    }

    /**
     * @param  mixed  $job
     * @return string
     */
    private function resolveJobClass(mixed $job): string
    {
        $name = $job->getName();

        if (str_contains($name, '\\')) {
            return $name;
        }

        $payload = json_decode($job->getRawBody(), true);

        return $payload['displayName'] ?? $name;
    }

    /**
     * @param  string  $jobClass
     * @return ?AuditJob
     */
    private function attribute(string $jobClass): ?AuditJob
    {
        if (! class_exists($jobClass)) {
            return null;
        }

        $attrs = (new \ReflectionClass($jobClass))->getAttributes(AuditJob::class);

        return $attrs ? $attrs[0]->newInstance() : null;
    }
}

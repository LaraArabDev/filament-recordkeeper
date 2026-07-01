<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobQueued;
use LaraArabDev\Recordkeeper\Attributes\AuditJob;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

#[AuditJob]
class AuditedJob
{
    public string $queue = 'default';
}

class NonAuditedJob {}

final class JobAuditingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('recordkeeper.jobs.enabled', false);
    }

    #[Test]
    public function job_with_attribute_is_audited_when_jobs_disabled(): void
    {
        $this->fireProcessed(AuditedJob::class);

        $audit = Audit::where('event', 'job.processed')->first();

        $this->assertNotNull($audit);
        $this->assertSame('job', $audit->auditable_type);
        $this->assertSame(AuditedJob::class, $audit->context['job']);
    }

    #[Test]
    public function job_without_attribute_is_not_audited_when_jobs_disabled(): void
    {
        $this->fireProcessed(NonAuditedJob::class);

        $this->assertSame(0, Audit::where('event', 'job.processed')->count());
    }

    #[Test]
    public function all_jobs_audited_when_jobs_enabled(): void
    {
        config(['recordkeeper.jobs.enabled' => true]);

        $this->fireProcessed(NonAuditedJob::class);

        $this->assertSame(1, Audit::where('event', 'job.processed')->count());
    }

    #[Test]
    public function excluded_job_is_not_audited(): void
    {
        config([
            'recordkeeper.jobs.enabled' => true,
            'recordkeeper.jobs.exclude' => [AuditedJob::class],
        ]);

        $this->fireProcessed(AuditedJob::class);

        $this->assertSame(0, Audit::where('event', 'job.processed')->count());
    }

    #[Test]
    public function job_queued_event_is_audited(): void
    {
        $job = new AuditedJob;

        event(new JobQueued('sync', 'default', 'queued-id', $job, [], null));

        $this->assertSame(1, Audit::where('event', 'job.queued')->count());
    }

    #[Test]
    public function job_failed_context_includes_exception(): void
    {
        config(['recordkeeper.jobs.enabled' => true]);

        $this->fireFailed(NonAuditedJob::class, 'Something went wrong');

        $audit = Audit::where('event', 'job.failed')->first();

        $this->assertNotNull($audit);
        $this->assertSame('Something went wrong', $audit->context['exception']);
    }

    #[Test]
    public function model_scope_job_audits(): void
    {
        config(['recordkeeper.jobs.enabled' => true]);

        $this->fireProcessed(NonAuditedJob::class);
        Audit::create(['event' => 'updated', 'auditable_type' => 'system', 'old_values' => [], 'new_values' => []]);

        $this->assertSame(1, Audit::jobAudits()->count());
    }

    #[Test]
    public function job_class_resolved_from_display_name_when_name_has_no_namespace(): void
    {
        $mock = $this->createMock(Job::class);
        $mock->method('getName')->willReturn('audited-job');
        $mock->method('getRawBody')->willReturn(json_encode(['displayName' => AuditedJob::class]));
        $mock->method('getQueue')->willReturn('default');
        $mock->method('attempts')->willReturn(1);

        event(new JobProcessed('sync', $mock));

        $audit = Audit::where('event', 'job.processed')->first();

        $this->assertNotNull($audit);
        $this->assertSame(AuditedJob::class, $audit->context['job']);
    }

    #[Test]
    public function job_class_falls_back_to_name_when_display_name_absent(): void
    {
        config(['recordkeeper.jobs.enabled' => true]);

        $mock = $this->createMock(Job::class);
        $mock->method('getName')->willReturn('plain-job');
        $mock->method('getRawBody')->willReturn(json_encode([]));
        $mock->method('getQueue')->willReturn('default');
        $mock->method('attempts')->willReturn(1);

        event(new JobProcessed('sync', $mock));

        $audit = Audit::where('event', 'job.processed')->first();

        $this->assertNotNull($audit);
        $this->assertSame('plain-job', $audit->context['job']);
    }

    private function fireProcessed(string $jobClass): void
    {
        $job = $this->mockQueueJob($jobClass);
        event(new JobProcessed('sync', $job));
    }

    private function fireFailed(string $jobClass, string $message): void
    {
        $job = $this->mockQueueJob($jobClass);
        event(new JobFailed('sync', $job, new \RuntimeException($message)));
    }

    private function mockQueueJob(string $jobClass): Job
    {
        $mock = $this->createMock(Job::class);
        $mock->method('getName')->willReturn($jobClass);
        $mock->method('getRawBody')->willReturn(json_encode(['displayName' => $jobClass]));
        $mock->method('getQueue')->willReturn('default');
        $mock->method('attempts')->willReturn(1);

        return $mock;
    }
}

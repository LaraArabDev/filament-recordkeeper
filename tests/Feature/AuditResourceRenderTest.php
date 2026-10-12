<?php

declare(strict_types=1);

namespace LaraArabDev\RecordkeeperFilament\Tests\Feature;

use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Models\AuditHttpRequest;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ListAudits;
use LaraArabDev\RecordkeeperFilament\Resources\Pages\ViewAudit;
use LaraArabDev\RecordkeeperFilament\Tests\TestCase;
use LaraArabDev\RecordkeeperFilament\Widgets\AuditTimeline;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class AuditResourceRenderTest extends TestCase
{
    private function auditableType(): string
    {
        return Audit::class;
    }

    #[Test]
    public function list_page_renders_successfully(): void
    {
        Livewire::test(ListAudits::class)
            ->assertSuccessful();
    }

    #[Test]
    public function list_page_renders_table_columns(): void
    {
        Audit::insert([
            [
                'event' => 'created',
                'auditable_type' => $this->auditableType(),
                'auditable_id' => 1,
                'user_id' => 5,
                'user_type' => 'App\\Models\\User',
                'guard' => 'web',
                'ip_address' => '127.0.0.1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Livewire::test(ListAudits::class)
            ->assertCanSeeTableRecords(Audit::all())
            ->assertCanRenderTableColumn('created_at')
            ->assertCanRenderTableColumn('event')
            ->assertCanRenderTableColumn('auditable_type')
            ->assertCanRenderTableColumn('user_id');
    }

    #[Test]
    public function list_page_can_filter_by_event(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'deleted', 'auditable_type' => $this->auditableType(), 'auditable_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(ListAudits::class)
            ->filterTable('event', ['created'])
            ->assertCanSeeTableRecords(Audit::where('event', 'created')->get())
            ->assertCanNotSeeTableRecords(Audit::where('event', '!=', 'created')->get());
    }

    #[Test]
    public function list_page_can_filter_by_model(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'created', 'auditable_type' => AuditHttpRequest::class, 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(ListAudits::class)
            ->filterTable('auditable_type', [$this->auditableType()])
            ->assertCanSeeTableRecords(Audit::where('auditable_type', $this->auditableType())->get())
            ->assertCanNotSeeTableRecords(Audit::where('auditable_type', AuditHttpRequest::class)->get());
    }

    #[Test]
    public function list_page_can_filter_by_rollbackable(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'route.visited', 'auditable_type' => $this->auditableType(), 'auditable_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(ListAudits::class)
            ->filterTable('rollbackable', true)
            ->assertCanSeeTableRecords(Audit::where('event', 'created')->get())
            ->assertCanNotSeeTableRecords(Audit::where('event', 'route.visited')->get());
    }

    #[Test]
    public function list_page_sorts_by_created_at_desc_by_default(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now()->subMinutes(2), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(ListAudits::class)
            ->assertCanSeeTableRecords(Audit::latest()->get(), inOrder: true);
    }

    #[Test]
    public function list_page_has_view_and_revert_actions(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(ListAudits::class)
            ->assertTableActionExists('view')
            ->assertTableActionExists('revert');
    }

    #[Test]
    public function view_page_renders_successfully(): void
    {
        $audit = Audit::create([
            'event' => 'updated',
            'auditable_type' => $this->auditableType(),
            'auditable_id' => 1,
            'user_id' => 3,
            'user_type' => 'App\\Models\\User',
            'guard' => 'web',
            'old_values' => ['status' => 'pending'],
            'new_values' => ['status' => 'shipped'],
        ]);

        Livewire::test(ViewAudit::class, ['record' => $audit->getKey()])
            ->assertSuccessful();
    }

    #[Test]
    public function timeline_widget_renders_successfully(): void
    {
        Livewire::test(AuditTimeline::class)
            ->assertSuccessful();
    }

    #[Test]
    public function timeline_widget_renders_columns(): void
    {
        Audit::insert([
            [
                'event' => 'created',
                'auditable_type' => $this->auditableType(),
                'auditable_id' => 1,
                'user_id' => 3,
                'user_type' => 'App\\Models\\User',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Livewire::test(AuditTimeline::class)
            ->assertCanRenderTableColumn('created_at')
            ->assertCanRenderTableColumn('event')
            ->assertCanRenderTableColumn('auditable_type')
            ->assertCanRenderTableColumn('user_id');
    }

    #[Test]
    public function timeline_widget_shows_records(): void
    {
        Audit::insert([
            ['event' => 'created', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['event' => 'updated', 'auditable_type' => $this->auditableType(), 'auditable_id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::test(AuditTimeline::class)
            ->assertCanSeeTableRecords(Audit::all());
    }
}

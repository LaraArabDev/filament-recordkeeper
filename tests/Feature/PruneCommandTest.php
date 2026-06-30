<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Support\Carbon;
use LaraArabDev\Recordkeeper\Models\Audit;
use LaraArabDev\Recordkeeper\Support\AttributeResolver;
use LaraArabDev\Recordkeeper\Tests\Fixtures\Order;
use LaraArabDev\Recordkeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class PruneCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        AttributeResolver::clearCache();
    }

    #[Test]
    public function prunes_entries_past_retention(): void
    {
        // Create an old audit record
        Order::create(['status' => 'old']);
        Audit::query()->update(['created_at' => Carbon::now()->subDays(400)]);

        $this->artisan('recordkeeper:prune', ['--days' => 365, '--yes' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('audits', 0);
    }

    #[Test]
    public function keeps_entries_inside_retention(): void
    {
        // Create a recent audit record
        Order::create(['status' => 'new']);
        // created_at is now, which is within 365 days

        $this->artisan('recordkeeper:prune', ['--days' => 365, '--yes' => true])
            ->assertExitCode(0);

        $this->assertDatabaseCount('audits', 1);
    }
}

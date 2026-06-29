<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Tests\Feature;

use Illuminate\Support\Facades\Queue;
use LaraArabDev\Recordkeeper\Jobs\WriteHttpRequest;
use LaraArabDev\Recordkeeper\Models\AuditHttpRequest;
use LaraArabDev\Recordkeeper\Tests\TestCase;

final class WriteHttpRequestJobTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('recordkeeper.http.enabled', true);
    }

    public function test_handle_creates_audit_http_request(): void
    {
        $job = new WriteHttpRequest([
            'method'      => 'POST',
            'url'         => 'https://api.stripe.com/v1/charges',
            'status_code' => 201,
            'duration_ms' => 245,
            'failed'      => false,
            'created_at'  => now(),
        ]);

        $job->handle();

        $this->assertSame(1, AuditHttpRequest::count());

        $record = AuditHttpRequest::first();
        $this->assertSame('POST', $record->method);
        $this->assertSame('https://api.stripe.com/v1/charges', $record->url);
        $this->assertSame(201, $record->status_code);
        $this->assertSame(245, $record->duration_ms);
        $this->assertFalse($record->failed);
    }

    public function test_handle_stores_null_audit_id_when_not_provided(): void
    {
        $job = new WriteHttpRequest([
            'method'      => 'GET',
            'url'         => 'https://api.example.com/data',
            'status_code' => 200,
            'failed'      => false,
            'created_at'  => now(),
        ]);

        $job->handle();

        $this->assertNull(AuditHttpRequest::first()->audit_id);
    }

    public function test_handle_stores_failed_connection(): void
    {
        $job = new WriteHttpRequest([
            'method'      => 'GET',
            'url'         => 'https://unreachable.example.com',
            'status_code' => null,
            'duration_ms' => null,
            'failed'      => true,
            'created_at'  => now(),
        ]);

        $job->handle();

        $record = AuditHttpRequest::first();
        $this->assertTrue($record->failed);
        $this->assertNull($record->status_code);
    }

    public function test_queue_dispatched_when_http_queue_enabled(): void
    {
        Queue::fake();
        config([
            'recordkeeper.http.enabled' => true,
            'recordkeeper.http.queue'   => true,
        ]);

        $request  = $this->makeRequest('GET', 'https://api.stripe.com/v1/charges');
        $response = $this->makeResponse(200);

        event(new \Illuminate\Http\Client\Events\RequestSending($request));
        event(new \Illuminate\Http\Client\Events\ResponseReceived($request, $response));

        Queue::assertPushed(WriteHttpRequest::class);
        $this->assertSame(0, AuditHttpRequest::count());
    }

    public function test_no_job_dispatched_when_http_queue_disabled(): void
    {
        Queue::fake();
        config([
            'recordkeeper.http.enabled' => true,
            'recordkeeper.http.queue'   => false,
        ]);

        $request  = $this->makeRequest('GET', 'https://api.stripe.com/v1/charges');
        $response = $this->makeResponse(200);

        event(new \Illuminate\Http\Client\Events\RequestSending($request));
        event(new \Illuminate\Http\Client\Events\ResponseReceived($request, $response));

        Queue::assertNothingPushed();
        $this->assertSame(1, AuditHttpRequest::count());
    }

    public function test_queue_dispatched_on_failed_connection_when_enabled(): void
    {
        Queue::fake();
        config([
            'recordkeeper.http.enabled' => true,
            'recordkeeper.http.queue'   => true,
        ]);

        $request   = $this->makeRequest('GET', 'https://api.example.com');
        $exception = new \Illuminate\Http\Client\ConnectionException('refused');

        event(new \Illuminate\Http\Client\Events\RequestSending($request));
        event(new \Illuminate\Http\Client\Events\ConnectionFailed($request, $exception));

        Queue::assertPushed(WriteHttpRequest::class);
        $this->assertSame(0, AuditHttpRequest::count());
    }

    public function test_queue_uses_custom_queue_name(): void
    {
        Queue::fake();
        config([
            'recordkeeper.http.enabled'    => true,
            'recordkeeper.http.queue'      => true,
            'recordkeeper.http.queue_name' => 'http-logs',
        ]);

        $request  = $this->makeRequest('GET', 'https://api.stripe.com');
        $response = $this->makeResponse(200);

        event(new \Illuminate\Http\Client\Events\RequestSending($request));
        event(new \Illuminate\Http\Client\Events\ResponseReceived($request, $response));

        Queue::assertPushedOn('http-logs', WriteHttpRequest::class);
    }

    private function makeRequest(string $method, string $url): \Illuminate\Http\Client\Request
    {
        $psrRequest = new \GuzzleHttp\Psr7\Request($method, $url, ['Content-Type' => 'application/json']);

        return new \Illuminate\Http\Client\Request($psrRequest);
    }

    private function makeResponse(int $status): \Illuminate\Http\Client\Response
    {
        $psrResponse = new \GuzzleHttp\Psr7\Response($status, [], '{}');

        return new \Illuminate\Http\Client\Response($psrResponse);
    }
}

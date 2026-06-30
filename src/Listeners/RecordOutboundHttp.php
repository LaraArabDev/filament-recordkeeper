<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use LaraArabDev\Recordkeeper\Jobs\WriteHttpRequest;
use LaraArabDev\Recordkeeper\Models\AuditHttpRequest;
use LaraArabDev\Recordkeeper\Support\HttpTracker;

final class RecordOutboundHttp
{
    public function __construct(private readonly HttpTracker $tracker) {}

    /** @return array<string, string> */
    public function subscribe(Dispatcher $events): array
    {
        return [
            RequestSending::class => 'onSending',
            ResponseReceived::class => 'onReceived',
            ConnectionFailed::class => 'onFailed',
        ];
    }

    public function onSending(RequestSending $event): void
    {
        if (! config('recordkeeper.http.enabled', false) || ! $this->shouldRecord($event->request->url())) {
            return;
        }

        $this->tracker->startRequest($event->request, microtime(true));
    }

    public function onReceived(ResponseReceived $event): void
    {
        if (! config('recordkeeper.http.enabled', false) || ! $this->shouldRecord($event->request->url())) {
            return;
        }

        $timing = $this->tracker->finishRequest($event->request, microtime(true));

        $data = [
            'audit_id' => $this->tracker->currentAuditId(),
            'method' => strtoupper($event->request->method()),
            'url' => $event->request->url(),
            'status_code' => $event->response->status(),
            'duration_ms' => $timing['duration_ms'] ?? null,
            'failed' => false,
            'created_at' => now(),
        ];

        if (config('recordkeeper.http.capture_headers', false)) {
            $data['request_headers'] = $event->request->headers();
            $data['response_headers'] = $event->response->headers();
        }

        if (config('recordkeeper.http.capture_body', false)) {
            $limit = (int) config('recordkeeper.http.body_limit', 1000);
            $body = $event->response->body();
            $data['response_body'] = strlen($body) > $limit ? substr($body, 0, $limit) : $body;
        }

        $this->persist($data);
    }

    public function onFailed(ConnectionFailed $event): void
    {
        if (! config('recordkeeper.http.enabled', false) || ! $this->shouldRecord($event->request->url())) {
            return;
        }

        $timing = $this->tracker->finishRequest($event->request, microtime(true));

        $this->persist([
            'audit_id' => $this->tracker->currentAuditId(),
            'method' => strtoupper($event->request->method()),
            'url' => $event->request->url(),
            'status_code' => null,
            'duration_ms' => $timing['duration_ms'] ?? null,
            'failed' => true,
            'created_at' => now(),
        ]);
    }

    private function persist(array $data): void
    {
        if (config('recordkeeper.http.queue', false)) {
            $connection = config('recordkeeper.queue.connection');
            $queue = config('recordkeeper.http.queue_name', config('recordkeeper.queue.queue', 'audits'));

            dispatch(new WriteHttpRequest($data))
                ->onConnection($connection)
                ->onQueue($queue);

            return;
        }

        AuditHttpRequest::create($data);
    }

    private function shouldRecord(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST) ?? '';
        $excluded = config('recordkeeper.http.exclude_hosts', []);

        return ! in_array($host, $excluded, true);
    }
}

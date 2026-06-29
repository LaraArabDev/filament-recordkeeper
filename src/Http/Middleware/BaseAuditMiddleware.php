<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LaraArabDev\Recordkeeper\Actions\RedactValues;
use LaraArabDev\Recordkeeper\Models\Audit;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseAuditMiddleware
{
    public function __construct(
        private readonly RedactValues $redactValues,
    ) {}

    /** @return string */
    abstract protected function guard(): string;

    /**
     * @param  Request  $request
     * @return mixed
     */
    protected function resolveActor(Request $request): mixed
    {
        return auth()->guard($this->guard())->user();
    }

    /**
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string   ...$options
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$options): Response
    {
        $opts = $this->parseOptions($options);

        if ($opts['sample'] < 1.0 && (mt_rand() / mt_getrandmax()) > $opts['sample']) {
            return $next($request);
        }

        if (! config('recordkeeper.enabled', true)) {
            return $next($request);
        }

        $start    = microtime(true);
        $response = $next($request);
        $duration = (int) ((microtime(true) - $start) * 1000);

        try {
            $this->record($request, $response, $opts, $duration);
        } catch (\Throwable $e) {
            if (config('recordkeeper.strict', false)) {
                throw $e;
            }
            Log::error('[Recordkeeper] Middleware audit failed: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * @param  Request   $request
     * @param  Response  $response
     * @param  array     $opts
     * @param  int       $duration
     * @return void
     */
    protected function record(Request $request, Response $response, array $opts, int $duration): void
    {
        $user = $this->resolveActor($request);

        $body = [];
        if ($opts['body']) {
            $body = ($this->redactValues)($request->all());
        }

        $tags = [];
        if (! empty($opts['tag'])) {
            $tags[] = $opts['tag'];
        }

        $audit = new Audit();
        $audit->fill([
            'event'          => 'route.' . strtolower($request->method()),
            'auditable_type' => 'route',
            'auditable_id'   => null,
            'old_values'     => [],
            'new_values'     => $body,
            'user_type'      => $user ? $user::class : null,
            'user_id'        => $user?->getKey(),
            'url'            => $request->fullUrl(),
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
            'tags'           => implode(',', $tags),
            'guard'          => $this->guard(),
            'batch_id'       => null,
            'context'        => [
                'route'       => $request->route()?->getName() ?? $request->path(),
                'method'      => $request->method(),
                'status'      => $response->getStatusCode(),
                'duration_ms' => $duration,
            ],
        ]);
        $audit->save();
    }

    /**
     * @param  array  $options
     * @return array
     */
    protected function parseOptions(array $options): array
    {
        $opts = [
            'tag'      => null,
            'body'     => false,
            'response' => false,
            'sample'   => 1.0,
        ];

        foreach ($options as $option) {
            if (! str_contains($option, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $option, 2);
            $key           = trim($key);
            $value         = trim($value);

            $opts[$key] = match ($key) {
                'body', 'response' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
                'sample'           => (float) $value,
                default            => $value,
            };
        }

        return $opts;
    }
}

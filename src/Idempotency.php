<?php

declare(strict_types = 1);

namespace Eufaturo\IdempotencyMiddleware;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Cache\Repository as Cache;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Ramsey\Uuid\Rfc4122\Fields as UuidFields;
use Ramsey\Uuid\UuidFactory;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class Idempotency
{
    /**
     * Name of the main idempotency HTTP header.
     */
    private const string IDEMPOTENCY_HEADER = 'Idempotency-Key';

    /**
     * Name of the repeated idempotency HTTP header.
     */
    private const string IDEMPOTENCY_REPEATED_HEADER = 'Idempotent-Replayed';

    /**
     * The idempotency key expiration time, in minutes.
     *
     * Defaults to 6 hours.
     */
    private const int IDEMPOTENCY_EXPIRATION = 360;

    public function __construct(
        private readonly Cache $cache,
        private readonly ResponseFactory $response,
        private readonly UuidFactory $uuidFactory,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->isIdempotentRequest($request)) {
            return $next($request);
        }

        if (! $this->isValidIdempotentKey($request)) {
            throw new IdempotencyException(
                message: 'The given idempotency key is invalid. Please ensure the key is a valid UUID value.',
            );
        }

        if ($this->requestIsRepeated($request)) {
            if ($this->requestContentsDoNotMatch($request)) {
                throw new IdempotencyException(
                    message: 'A resource has been created with this idempotency key but with different content.',
                );
            }

            if ($this->requestPathDoNotMatch($request)) {
                throw new IdempotencyException(
                    message: 'A resource has been created with this idempotency key but on a different endpoint.',
                );
            }

            $cache = $this->cache->get($this->getCacheKey($request));

            return $this->response->make($cache['body'], $cache['status'], array_merge($cache['headers'], [
                self::IDEMPOTENCY_REPEATED_HEADER => $this->getIdempotencyKey($request),
            ]));
        }

        return $this->processRequest($request, $next);
    }

    private function getIdempotencyKey(Request $request): string
    {
        return $request->header(self::IDEMPOTENCY_HEADER);
    }

    private function getCacheKey(Request $request): string
    {
        return implode('-', [
            $request->bearerToken(),
            $this->getIdempotencyKey($request),
        ]);
    }

    private function isIdempotentRequest(Request $request): bool
    {
        return $request->isMethod('POST') && $request->hasHeader(self::IDEMPOTENCY_HEADER);
    }

    private function isValidIdempotentKey(Request $request): bool
    {
        try {
            $uuidString = $this->getIdempotencyKey($request);

            $uuid = $this->uuidFactory->fromString($uuidString);

            $uuidFields = new UuidFields($uuid->getBytes());

            return $uuidFields->getVersion() === 4;
        } catch (Throwable) {
            return false;
        }
    }

    private function requestContentsDoNotMatch(Request $request): bool
    {
        $cache = $this->cache->get($this->getCacheKey($request));

        return $request->getContent() !== $cache['request_body'];
    }

    private function requestPathDoNotMatch(Request $request): bool
    {
        $cache = $this->cache->get($this->getCacheKey($request));

        return $request->path() !== $cache['path'];
    }

    private function requestIsRepeated(Request $request): bool
    {
        return $this->cache->has($this->getCacheKey($request));
    }

    private function processRequest(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($response->getStatusCode() !== Response::HTTP_UNPROCESSABLE_ENTITY) {
            $this->cache->put($this->getCacheKey($request), [
                'body'         => $response->getContent(),
                'status'       => $response->getStatusCode(),
                'headers'      => $response->headers->all(),
                'path'         => $request->path(),
                'request_body' => $request->getContent(),
            ], CarbonImmutable::now()->addMinutes(self::IDEMPOTENCY_EXPIRATION));
        }

        return $response;
    }
}

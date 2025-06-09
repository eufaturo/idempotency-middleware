<?php

declare(strict_types = 1);

namespace Eufaturo\IdempotencyMiddleware\Tests\Acceptance;

use Eufaturo\IdempotencyMiddleware\Idempotency;
use Eufaturo\IdempotencyMiddleware\Tests\TestCase;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestDox;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class IdempotencyTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('A get request without the idempotency header will not trigger the middleware')]
    public function test_1(): void
    {
        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $request = Request::create('/', 'GET', [], [], [], [], 'Test body');

        $result = $middleware->handle(
            request: $request,
            next: fn () => new Response('All is ok!', 200),
        );

        $this->assertSame('All is ok!', $result->getContent());
        $this->assertSame(200, $result->getStatusCode());
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('A get request wit the idempotency header will not trigger the middleware')]
    public function test_2(): void
    {
        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = 'fake-idempotency-key';

        $request = Request::create('/', 'GET', [], [], [], [], 'Test body');
        $request->headers->set('Idempotency-Key', $idempotencyKey);

        $result = $middleware->handle(
            request: $request,
            next: fn () => new Response('All is ok!', 200),
        );

        $this->assertSame('All is ok!', $result->getContent());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertFalse(Cache::has($this->generateCacheKey($request, $idempotencyKey)));
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('A post request without the idempotency header will not trigger the middleware')]
    public function test_3(): void
    {
        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $request = Request::create('/', 'POST', [], [], [], [], 'Test body');

        $result = $middleware->handle(
            request: $request,
            next: fn () => new Response('All is ok!', 200),
        );

        $this->assertSame('All is ok!', $result->getContent());
        $this->assertSame(200, $result->getStatusCode());
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('A post request with an invalid idempotency header will trigger the middleware')]
    public function test_4(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('The given idempotency key is invalid. Please ensure the key is a valid UUID value.');

        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = 'fake-idempotency-key';

        $request = Request::create('/', 'POST', [], [], [], [], 'Test body');
        $request->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request,
            next: fn () => new Response('All is ok!', 200),
        );
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('A post request with a valid idempotency header will trigger the middleware')]
    public function test_5(): void
    {
        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = Uuid::uuid4()->toString();

        $request = Request::create('/', 'POST', [], [], [], [], 'Test body');
        $request->headers->set('Idempotency-Key', $idempotencyKey);

        $result = $middleware->handle(
            request: $request,
            next: fn () => new Response('All is ok!', 200),
        );

        $this->assertSame('All is ok!', $result->getContent());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertTrue(Cache::has($this->generateCacheKey($request, $idempotencyKey)));
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('Using the same idempotency key but different content body will trigger an exception')]
    public function test_6(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('A resource has been created with this idempotency key but with different content.');

        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = Uuid::uuid4()->toString();

        $request1 = Request::create('/', 'POST', [], [], [], [], 'Test body 1');
        $request1->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request1,
            next: fn () => new Response('All is ok!', 200),
        );

        $request2 = Request::create('/', 'POST', [], [], [], [], 'Test body 2');
        $request2->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request2,
            next: fn () => new Response('All is ok!', 200),
        );
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('Using the same idempotency key with the same content body but on a different endpoint will trigger an exception')]
    public function test_7(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('A resource has been created with this idempotency key but on a different endpoint.');

        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = Uuid::uuid4()->toString();

        $request1 = Request::create('/', 'POST', [], [], [], [], 'Test body');
        $request1->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request1,
            next: fn () => new Response('All is ok!', 200),
        );

        $request2 = Request::create('/other-endpoint', 'POST', [], [], [], [], 'Test body');
        $request2->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request2,
            next: fn () => new Response('All is ok!', 200),
        );
    }

    /**
     * @throws BindingResolutionException
     */
    #[Test]
    #[TestDox('Using the same idempotency key with the same content body and the same endpoint will return the cached response')]
    public function test_8(): void
    {
        /** @var Idempotency $middleware */
        $middleware = $this->app?->make(Idempotency::class);

        $idempotencyKey = Uuid::uuid4()->toString();

        $request1 = Request::create('/', 'POST', [], [], [], [], 'Test body');
        $request1->headers->set('Idempotency-Key', $idempotencyKey);

        $middleware->handle(
            request: $request1,
            next: fn () => new Response('All is ok!', 200),
        );

        $request2 = Request::create('/', 'POST', [], [], [], [], 'Test body');
        $request2->headers->set('Idempotency-Key', $idempotencyKey);

        $result = $middleware->handle(
            request: $request2,
            next: fn () => new Response('All is ok!', 200),
        );

        $this->assertSame('All is ok!', $result->getContent());
        $this->assertSame(200, $result->getStatusCode());
        $this->assertSame($idempotencyKey, $result->headers->get('Idempotent-Replayed'));
    }

    private function generateCacheKey($request, string $idempotencyKey): string
    {
        return implode('-', [
            $request->bearerToken(),
            $idempotencyKey,
        ]);
    }
}

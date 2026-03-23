<?php

declare(strict_types=1);

namespace ApiVersioning\Tests\Unit\Manager;

use ApiVersioning\Contract\ApiVersionInterface;
use ApiVersioning\Context\RouteContext;
use ApiVersioning\Event\AfterVersionRequestEvent;
use ApiVersioning\Event\AfterVersionResponseEvent;
use ApiVersioning\Event\ApiVersionEvents;
use ApiVersioning\Event\BeforeVersionRequestEvent;
use ApiVersioning\Event\BeforeVersionResponseEvent;
use ApiVersioning\Manager\ApiVersionEventManager;
use ApiVersioning\Provider\DefaultApiVersionProvider;
use ApiVersioning\Resolver\DefaultApiVersionResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ApiVersionEventManagerTest extends TestCase
{
    private function makeVersion(string $name, array &$calls = []): ApiVersionInterface
    {
        return new class($name, $calls) implements ApiVersionInterface {
            public function __construct(
                private readonly string $name,
                private array &$calls,
            ) {}

            public function getName(): string { return $this->name; }
            public function getDescription(): string { return 'Version ' . $this->name; }

            public function onRequest(RouteContext $context, Request $request): void
            {
                $this->calls[] = 'request:' . $this->name;
            }

            public function onResponse(RouteContext $context, Response $response): void
            {
                $this->calls[] = 'response:' . $this->name;
            }
        };
    }

    public function testHandleRequestDoesNothingWhenResolverReturnsNull(): void
    {
        $calls = [];
        $version = $this->makeVersion('2.0.0', $calls);
        $provider = new DefaultApiVersionProvider([$version]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('dispatch');

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);
        $request = Request::create('/');

        $manager->handleRequest(new RouteContext('test_route'), $request);

        $this->assertEmpty($calls);
    }

    public function testOnlyVersionsGreaterThanCurrentReceiveOnRequest(): void
    {
        $calls = [];
        $v100 = $this->makeVersion('1.0.0', $calls);
        $v200 = $this->makeVersion('2.0.0', $calls);
        $v300 = $this->makeVersion('3.0.0', $calls);

        $provider = new DefaultApiVersionProvider([$v100, $v200, $v300]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);

        $request = Request::create('/');
        $request->headers->set('X-API-Version', '2.0.0');

        $manager->handleRequest(new RouteContext('test_route'), $request);

        $this->assertSame(['request:3.0.0'], $calls);
    }

    public function testOnRequestCalledInAscendingOrder(): void
    {
        $calls = [];
        $v200 = $this->makeVersion('2.0.0', $calls);
        $v300 = $this->makeVersion('3.0.0', $calls);
        $v400 = $this->makeVersion('4.0.0', $calls);

        $provider = new DefaultApiVersionProvider([$v400, $v200, $v300]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);

        $request = Request::create('/');
        $request->headers->set('X-API-Version', '1.0.0');

        $manager->handleRequest(new RouteContext('test_route'), $request);

        $this->assertSame(['request:2.0.0', 'request:3.0.0', 'request:4.0.0'], $calls);
    }

    public function testOnResponseCalledInDescendingOrder(): void
    {
        $calls = [];
        $v200 = $this->makeVersion('2.0.0', $calls);
        $v300 = $this->makeVersion('3.0.0', $calls);
        $v400 = $this->makeVersion('4.0.0', $calls);

        $provider = new DefaultApiVersionProvider([$v400, $v200, $v300]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnArgument(0);

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);

        $request = Request::create('/');
        $request->headers->set('X-API-Version', '1.0.0');
        $response = new Response();

        $manager->handleResponse(new RouteContext('test_route'), $request, $response);

        $this->assertSame(['response:4.0.0', 'response:3.0.0', 'response:2.0.0'], $calls);
    }

    public function testEventsDispatchedInCorrectOrderForRequest(): void
    {
        $calls = [];
        $v200 = $this->makeVersion('2.0.0', $calls);

        $provider = new DefaultApiVersionProvider([$v200]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');

        $dispatchedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            function (object $event, string $eventName) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $eventName;
                return $event;
            }
        );

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);

        $request = Request::create('/');
        $request->headers->set('X-API-Version', '1.0.0');

        $manager->handleRequest(new RouteContext('test_route'), $request);

        $this->assertSame([
            ApiVersionEvents::BEFORE_VERSION_REQUEST,
            ApiVersionEvents::AFTER_VERSION_REQUEST,
        ], $dispatchedEvents);
    }

    public function testEventsDispatchedInCorrectOrderForResponse(): void
    {
        $calls = [];
        $v200 = $this->makeVersion('2.0.0', $calls);

        $provider = new DefaultApiVersionProvider([$v200]);
        $resolver = new DefaultApiVersionResolver('X-API-Version');

        $dispatchedEvents = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(
            function (object $event, string $eventName) use (&$dispatchedEvents): object {
                $dispatchedEvents[] = $eventName;
                return $event;
            }
        );

        $manager = new ApiVersionEventManager($provider, $resolver, $dispatcher);

        $request = Request::create('/');
        $request->headers->set('X-API-Version', '1.0.0');
        $response = new Response();

        $manager->handleResponse(new RouteContext('test_route'), $request, $response);

        $this->assertSame([
            ApiVersionEvents::BEFORE_VERSION_RESPONSE,
            ApiVersionEvents::AFTER_VERSION_RESPONSE,
        ], $dispatchedEvents);
    }
}

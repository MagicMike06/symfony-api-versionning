<?php

declare(strict_types=1);

namespace ApiVersioning\Manager;

use ApiVersioning\Contract\ApiVersionProviderInterface;
use ApiVersioning\Contract\ApiVersionResolverInterface;
use ApiVersioning\Context\RouteContext;
use ApiVersioning\Event\AfterVersionRequestEvent;
use ApiVersioning\Event\AfterVersionResponseEvent;
use ApiVersioning\Event\ApiVersionEvents;
use ApiVersioning\Event\BeforeVersionRequestEvent;
use ApiVersioning\Event\BeforeVersionResponseEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ApiVersionEventManager
{
    public function __construct(
        private readonly ApiVersionProviderInterface $provider,
        private readonly ApiVersionResolverInterface $resolver,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function handleRequest(RouteContext $context, Request $request): void
    {
        $currentVersion = $this->resolver->resolve($request);
        if ($currentVersion === null) {
            return;
        }

        $versions = array_filter(
            $this->provider->getVersions(),
            static fn ($version) => version_compare($version->getName(), $currentVersion, '>'),
        );

        foreach ($versions as $version) {
            $this->eventDispatcher->dispatch(
                new BeforeVersionRequestEvent($version->getName(), $version->getDescription(), $context, $request),
                ApiVersionEvents::BEFORE_VERSION_REQUEST,
            );

            $version->onRequest($context, $request);

            $this->eventDispatcher->dispatch(
                new AfterVersionRequestEvent($version->getName(), $version->getDescription(), $context, $request),
                ApiVersionEvents::AFTER_VERSION_REQUEST,
            );
        }
    }

    public function handleResponse(RouteContext $context, Request $request, Response $response): void
    {
        $currentVersion = $this->resolver->resolve($request);
        if ($currentVersion === null) {
            return;
        }

        $versions = array_filter(
            $this->provider->getVersions(),
            static fn ($version) => version_compare($version->getName(), $currentVersion, '>'),
        );

        $versions = array_reverse(array_values($versions));

        foreach ($versions as $version) {
            $this->eventDispatcher->dispatch(
                new BeforeVersionResponseEvent($version->getName(), $version->getDescription(), $context, $response),
                ApiVersionEvents::BEFORE_VERSION_RESPONSE,
            );

            $version->onResponse($context, $response);

            $this->eventDispatcher->dispatch(
                new AfterVersionResponseEvent($version->getName(), $version->getDescription(), $context, $response),
                ApiVersionEvents::AFTER_VERSION_RESPONSE,
            );
        }
    }
}

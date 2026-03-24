<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\EventListener;

use MagicMike\ApiVersioning\Context\RouteContext;
use MagicMike\ApiVersioning\Manager\ApiVersionEventManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ApiVersioningKernelListener
{
    public function __construct(
        private readonly ApiVersionEventManager $manager,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request   = $event->getRequest();
        $routeName = $request->attributes->get('_route', '');

        $context = new RouteContext($routeName);
        $this->manager->handleRequest($context, $request);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request   = $event->getRequest();
        $response  = $event->getResponse();
        $routeName = $request->attributes->get('_route', '');

        $context = new RouteContext($routeName);
        $this->manager->handleResponse($context, $request, $response);
    }
}

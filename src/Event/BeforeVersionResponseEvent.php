<?php

declare(strict_types=1);

namespace ApiVersioning\Event;

use ApiVersioning\Context\RouteContext;
use Symfony\Component\HttpFoundation\Response;

class BeforeVersionResponseEvent extends AbstractApiVersionEvent
{
    public function __construct(
        string $versionName,
        string $versionDescription,
        RouteContext $routeContext,
        public readonly Response $response,
    ) {
        parent::__construct($versionName, $versionDescription, $routeContext);
    }
}

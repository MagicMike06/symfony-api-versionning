<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Event;

use MagicMike\ApiVersioning\Context\RouteContext;
use Symfony\Component\HttpFoundation\Response;

class AfterVersionResponseEvent extends AbstractApiVersionEvent
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

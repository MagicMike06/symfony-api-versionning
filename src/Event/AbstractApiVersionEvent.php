<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Event;

use MagicMike\ApiVersioning\Context\RouteContext;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractApiVersionEvent extends Event
{
    public function __construct(
        public readonly string $versionName,
        public readonly string $versionDescription,
        public readonly RouteContext $routeContext,
    ) {
    }
}

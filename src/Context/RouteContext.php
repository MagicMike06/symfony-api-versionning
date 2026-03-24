<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Context;

final readonly class RouteContext
{
    public function __construct(
        public string $routeName,
    ) {
    }
}

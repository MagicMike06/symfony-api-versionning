<?php

declare(strict_types=1);

namespace ApiVersioning\Resolver;

use ApiVersioning\Contract\ApiVersionResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class DefaultApiVersionResolver implements ApiVersionResolverInterface
{
    public function __construct(
        private readonly string $headerName = 'X-API-Version',
    ) {}

    public function resolve(Request $request): ?string
    {
        $value = $request->headers->get($this->headerName);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }
}

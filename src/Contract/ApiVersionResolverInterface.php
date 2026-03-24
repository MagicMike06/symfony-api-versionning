<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Contract;

use Symfony\Component\HttpFoundation\Request;

interface ApiVersionResolverInterface
{
    public function resolve(Request $request): ?string;
}

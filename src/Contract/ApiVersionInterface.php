<?php

declare(strict_types=1);

namespace ApiVersioning\Contract;

use ApiVersioning\Context\RouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiVersionInterface
{
    public function getName(): string;

    public function getDescription(): string;

    public function onRequest(RouteContext $context, Request $request): void;

    public function onResponse(RouteContext $context, Response $response): void;
}

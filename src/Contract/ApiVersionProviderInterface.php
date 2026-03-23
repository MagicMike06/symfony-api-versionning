<?php

declare(strict_types=1);

namespace ApiVersioning\Contract;

interface ApiVersionProviderInterface
{
    /**
     * @return ApiVersionInterface[] sorted ascending by version
     */
    public function getVersions(): array;
}

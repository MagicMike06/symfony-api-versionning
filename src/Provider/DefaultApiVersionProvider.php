<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Provider;

use MagicMike\ApiVersioning\Contract\ApiVersionInterface;
use MagicMike\ApiVersioning\Contract\ApiVersionProviderInterface;

class DefaultApiVersionProvider implements ApiVersionProviderInterface
{
    /** @var ApiVersionInterface[] */
    private array $versions;

    /**
     * @param iterable<ApiVersionInterface> $versions
     */
    public function __construct(iterable $versions)
    {
        $this->versions = \iterator_to_array($versions, false);
        \usort($this->versions, static fn (ApiVersionInterface $a, ApiVersionInterface $b) => \version_compare($a->getName(), $b->getName()));
    }

    public function getVersions(): array
    {
        return $this->versions;
    }
}

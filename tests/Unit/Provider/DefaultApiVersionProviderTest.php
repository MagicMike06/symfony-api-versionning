<?php

declare(strict_types=1);

namespace ApiVersioning\Tests\Unit\Provider;

use ApiVersioning\Context\RouteContext;
use ApiVersioning\Contract\ApiVersionInterface;
use ApiVersioning\Provider\DefaultApiVersionProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DefaultApiVersionProviderTest extends TestCase
{
    private function makeVersion(string $name): ApiVersionInterface
    {
        return new class($name) implements ApiVersionInterface {
            public function __construct(private readonly string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getDescription(): string
            {
                return '';
            }

            public function onRequest(RouteContext $context, Request $request): void
            {
            }

            public function onResponse(RouteContext $context, Response $response): void
            {
            }
        };
    }

    public function testSortsAscendingBySemver(): void
    {
        $v100 = $this->makeVersion('1.0.0');
        $v300 = $this->makeVersion('3.0.0');
        $v200 = $this->makeVersion('2.0.0');

        $provider = new DefaultApiVersionProvider([$v300, $v100, $v200]);
        $versions = $provider->getVersions();

        $this->assertSame(['1.0.0', '2.0.0', '3.0.0'], \array_map(static fn ($v) => $v->getName(), $versions));
    }

    public function testEmptyListReturnsEmptyArray(): void
    {
        $provider = new DefaultApiVersionProvider([]);
        $this->assertSame([], $provider->getVersions());
    }

    public function testSortsPatchVersionsCorrectly(): void
    {
        $v101 = $this->makeVersion('1.0.1');
        $v100 = $this->makeVersion('1.0.0');
        $v110 = $this->makeVersion('1.1.0');

        $provider = new DefaultApiVersionProvider([$v110, $v101, $v100]);
        $versions = $provider->getVersions();

        $this->assertSame(['1.0.0', '1.0.1', '1.1.0'], \array_map(static fn ($v) => $v->getName(), $versions));
    }
}

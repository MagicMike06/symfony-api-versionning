<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Tests\Unit\Resolver;

use MagicMike\ApiVersioning\Resolver\DefaultApiVersionResolver;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class DefaultApiVersionResolverTest extends TestCase
{
    public function testReturnsVersionWhenHeaderPresent(): void
    {
        $resolver = new DefaultApiVersionResolver();
        $request  = Request::create('/');
        $request->headers->set('X-API-Version', '2.0.0');

        $this->assertSame('2.0.0', $resolver->resolve($request));
    }

    public function testReturnsNullWhenHeaderAbsent(): void
    {
        $resolver = new DefaultApiVersionResolver();
        $request  = Request::create('/');

        $this->assertNull($resolver->resolve($request));
    }

    public function testReturnsNullWhenHeaderEmpty(): void
    {
        $resolver = new DefaultApiVersionResolver();
        $request  = Request::create('/');
        $request->headers->set('X-API-Version', '');

        $this->assertNull($resolver->resolve($request));
    }

    public function testUsesCustomHeaderName(): void
    {
        $resolver = new DefaultApiVersionResolver('X-Custom-Version');
        $request  = Request::create('/');
        $request->headers->set('X-Custom-Version', '1.5.0');

        $this->assertSame('1.5.0', $resolver->resolve($request));
    }

    public function testDefaultHeaderIgnoredWhenCustomConfigured(): void
    {
        $resolver = new DefaultApiVersionResolver('X-Custom-Version');
        $request  = Request::create('/');
        $request->headers->set('X-API-Version', '2.0.0');

        $this->assertNull($resolver->resolve($request));
    }
}

<?php

declare(strict_types=1);

namespace ApiVersioning;

use ApiVersioning\Compiler\ApiVersioningCompilerPass;
use ApiVersioning\DependencyInjection\ApiVersioningExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ApiVersioningBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ApiVersioningCompilerPass());
    }

    public function getContainerExtension(): ApiVersioningExtension
    {
        return new ApiVersioningExtension();
    }
}

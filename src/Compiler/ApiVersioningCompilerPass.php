<?php

declare(strict_types=1);

namespace MagicMike\ApiVersioning\Compiler;

use MagicMike\ApiVersioning\Provider\DefaultApiVersionProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApiVersioningCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(DefaultApiVersionProvider::class)) {
            return;
        }

        $explicitVersions = $container->getParameter('api_versioning.versions');

        if (!empty($explicitVersions)) {
            $references = [];
            foreach ($explicitVersions as $fqcn) {
                if (!$container->has($fqcn)) {
                    $container->register($fqcn, $fqcn)->setAutowired(true)->setAutoconfigured(true);
                }
                $references[] = new Reference($fqcn);
            }

            $container->getDefinition(DefaultApiVersionProvider::class)
                ->setArgument(0, $references);

            return;
        }

        $taggedServices = $container->findTaggedServiceIds('api_versioning.version');
        $references     = \array_map(
            static fn (string $id) => new Reference($id),
            \array_keys($taggedServices),
        );

        $container->getDefinition(DefaultApiVersionProvider::class)
            ->setArgument(0, $references);
    }
}

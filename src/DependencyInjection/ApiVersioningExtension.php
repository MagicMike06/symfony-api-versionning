<?php

declare(strict_types=1);

namespace ApiVersioning\DependencyInjection;

use ApiVersioning\Contract\ApiVersionInterface;
use ApiVersioning\Maker\MakeApiVersion;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class ApiVersioningExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!$config['enabled']) {
            return;
        }

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config'));
        $loader->load('services.yaml');

        $container->setParameter('api_versioning.header_name', $config['header_name']);
        $container->setParameter('api_versioning.versions', $config['versions']);

        $container->registerForAutoconfiguration(ApiVersionInterface::class)
            ->addTag('api_versioning.version');

        if (class_exists(AbstractMaker::class)) {
            $loader->load('maker.yaml');
        }
    }

    public function getAlias(): string
    {
        return 'api_versioning';
    }
}

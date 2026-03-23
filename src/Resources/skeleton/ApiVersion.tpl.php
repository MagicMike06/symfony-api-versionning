<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use ApiVersioning\Contract\ApiVersionInterface;
use ApiVersioning\Context\RouteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class <?= $class_name ?> implements ApiVersionInterface
{
    public function getName(): string
    {
        return '<?= $version ?>';
    }

    public function getDescription(): string
    {
        return '';
    }

    /**
     * Upgrade the incoming request from the previous version format to <?= $version ?> format.
     */
    public function onRequest(RouteContext $context, Request $request): void
    {
    }

    /**
     * Downgrade the outgoing response from <?= $version ?> format to the previous version format.
     */
    public function onResponse(RouteContext $context, Response $response): void
    {
    }
}

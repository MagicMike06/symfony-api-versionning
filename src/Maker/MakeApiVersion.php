<?php

declare(strict_types=1);

namespace ApiVersioning\Maker;

use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

class MakeApiVersion extends AbstractMaker
{
    public static function getCommandName(): string
    {
        return 'make:api-version';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates a new API version class (implements ApiVersionInterface)';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('version', InputArgument::OPTIONAL, 'The version number in semver format (e.g. <fg=yellow>2.0.0</>)')
            ->setHelp(
                <<<'EOF'
                The <info>%command.name%</info> command generates a skeleton class implementing
                <comment>ApiVersionInterface</comment> for the given version.

                <info>php %command.full_name% 2.0.0</info>

                The generated class will be placed in <comment>src/ApiVersion/</comment> and automatically
                picked up by the bundle via autoconfiguration.
                EOF
            )
        ;

        $inputConfig->setArgumentAsNonInteractive('version');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if ($input->getArgument('version')) {
            return;
        }

        $io->title('API Version Generator');

        $version = $io->ask(
            'What version number do you want to create? (semver, e.g. 2.0.0)',
            null,
            static function (?string $value): string {
                if (!$value || !preg_match('/^\d+\.\d+\.\d+$/', $value)) {
                    throw new \InvalidArgumentException('Version must follow semver format X.Y.Z (e.g. 2.0.0).');
                }

                return $value;
            }
        );

        $input->setArgument('version', $version);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $version = $input->getArgument('version');

        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            throw new \InvalidArgumentException(\sprintf('Version "%s" must follow semver format X.Y.Z (e.g. 2.0.0).', $version));
        }

        $className = 'V' . str_replace('.', '', $version);
        $classNameDetails = $generator->createClassNameDetails($className, 'ApiVersion\\');

        $fqcn = $classNameDetails->getFullName();
        $namespace = substr($fqcn, 0, strrpos($fqcn, '\\'));

        $generator->generateClass(
            $fqcn,
            __DIR__ . '/../Resources/skeleton/ApiVersion.tpl.php',
            [
                'version' => $version,
                'namespace' => $namespace,
                'class_name' => $classNameDetails->getShortName(),
            ],
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text([
            \sprintf('Class <comment>%s</comment> created.', $fqcn),
            '',
            'Next steps:',
            '  1. Open the generated class and implement <comment>onRequest()</comment>',
            '     to upgrade incoming requests from the previous version format.',
            '  2. Implement <comment>onResponse()</comment>',
            '     to downgrade outgoing responses back to the previous version format.',
            '  3. The class is automatically registered via autoconfiguration — no service declaration needed.',
        ]);
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }
}

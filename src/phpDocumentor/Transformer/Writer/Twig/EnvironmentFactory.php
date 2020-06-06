<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link http://phpdoc.org
 */

namespace phpDocumentor\Transformer\Writer\Twig;

use phpDocumentor\Descriptor\ProjectDescriptor;
use phpDocumentor\Path;
use phpDocumentor\Transformer\Transformation;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use function ltrim;

class EnvironmentFactory
{
    /** @var LinkRenderer */
    private $renderer;

    /** @var ?Path */
    private $templateOverridesAt;

    public function __construct(LinkRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function withTemplateOverridesAt(Path $path) : void
    {
        $this->templateOverridesAt = $path;
    }

    public function create(
        ProjectDescriptor $project,
        Transformation $transformation,
        string $destination
    ) : Environment {
        $mountManager = $transformation->template()->files();

        $loaders = [];
        if ($this->templateOverridesAt instanceof Path) {
            $loaders[] = new FilesystemLoader([(string) $this->templateOverridesAt]);
        }

        $loaders[] = new FlySystemLoader($mountManager->getFilesystem('template'), '', 'base');
        $loaders[] = new FlySystemLoader($mountManager->getFilesystem('templates'));

        $env = new Environment(new ChainLoader($loaders));

        $this->addPhpDocumentorExtension($project, $destination, $env);
        $this->enableDebug($env);

        return $env;
    }

    /**
     * Adds the phpDocumentor base extension to the Twig Environment.
     */
    private function addPhpDocumentorExtension(
        ProjectDescriptor $project,
        string $path,
        Environment $twigEnvironment
    ) : void {
        $extension = new Extension($project, $this->renderer);
        $extension->setDestination(ltrim($path, '/\\'));
        $twigEnvironment->addExtension($extension);
    }

    private function enableDebug(Environment $twigEnvironment) : void
    {
        $twigEnvironment->setCache(false);
        $twigEnvironment->enableDebug();
        $twigEnvironment->enableAutoReload();
        $twigEnvironment->addExtension(new DebugExtension());
    }
}

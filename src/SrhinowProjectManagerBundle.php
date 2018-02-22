<?php

/**
 * @copyright  Sven Rhinow <https://www.sr-tag.de>
 * @author     Sven Rhinow
 * @package    srhinow/project-manager-bundle
 * @license    LGPL-3.0+
 * @see	       https://github.com/srhinow/project-manager-bundle
 *
 */

namespace Srhinow\ProjectManagerBundle;


use Srhinow\ProjectManagerBundle\DependencyInjection\SrhinowProjectManagerExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Configures the Contao teaser-manager bundle.
 *
 * @author Sven Rhinow
 */
class SrhinowProjectManagerBundle extends Bundle
{
    /**
     * Builds the bundle.
     *
     * It is only ever called once when the cache is empty.
     *
     * This method can be overridden to register compilation passes,
     * other extensions, ...
     *
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new SrhinowProjectManagerExtension();
    }
}

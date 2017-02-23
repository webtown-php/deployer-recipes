<?php

namespace Webtown\DeployerRecipesBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 17:20
 */
class TemplatesPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * You can modify the container here before it is dumped to PHP code.
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('webtown_deployer.template_manager')) {
            return;
        }

        $templates = $this->findAndSortTaggedServices('webtown_deployer.template', $container);

        if (empty($warmers)) {
            return;
        }

        $container->getDefinition('webtown_deployer.template_manager')->addMethodCall('setTemplates', $templates);
    }
}

<?php

namespace Webtown\DeployerRecipesBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 17:20
 */
class TemplatesPass implements CompilerPassInterface
{
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

        if (empty($templates)) {
            return;
        }

        $container->getDefinition('webtown_deployer.template_manager')->addMethodCall('setTemplates', [$templates]);
    }

    /**
     * Copy from SF3 for SF2 compatibility. (original: \Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait)
     * @see \Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait
     *
     * Finds all services with the given tag name and order them by their priority.
     *
     * The order of additions must be respected for services having the same priority,
     * and knowing that the \SplPriorityQueue class does not respect the FIFO method,
     * we should not use this class.
     *
     * @see https://bugs.php.net/bug.php?id=53710
     * @see https://bugs.php.net/bug.php?id=60926
     *
     * @param string           $tagName
     * @param ContainerBuilder $container
     *
     * @return Reference[]
     */
    private function findAndSortTaggedServices($tagName, ContainerBuilder $container)
    {
        $services = array();

        foreach ($container->findTaggedServiceIds($tagName) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                $priority = isset($attributes['priority']) ? $attributes['priority'] : 0;
                $services[$priority][] = new Reference($serviceId);
            }
        }

        if ($services) {
            krsort($services);
            $services = call_user_func_array('array_merge', $services);
        }

        return $services;
    }
}

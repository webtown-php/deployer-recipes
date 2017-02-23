<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 18:33
 */

namespace Webtown\DeployerRecipesBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Webtown\DeployerRecipesBundle\DependencyInjection\Compiler\TemplatesPass;

class WebtownDeployerRecipesBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new TemplatesPass());
    }
}

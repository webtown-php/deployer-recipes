<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:38
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;

class TestTemplating implements EngineInterface
{
    public function render($name, array $parameters = array())
    {
        if (!file_exists($name)) {
            throw new \Exception('File doesn\'t exist: ' . $name);
        }

        $contents = file_get_contents($name);

        foreach ($parameters as $key => $value) {
            $contents = str_replace(sprintf('{{ %s }}', $key), $value, $contents);
        }

        return $contents;
    }

    /**
     * Returns true if the template exists.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     *
     * @return bool true if the template exists, false otherwise
     *
     * @throws \RuntimeException if the engine cannot handle the template name
     */
    public function exists($name)
    {
    }

    /**
     * Returns true if this class is able to render the given template.
     *
     * @param string|TemplateReferenceInterface $name A template name or a TemplateReferenceInterface instance
     *
     * @return bool true if this class supports the given template, false otherwise
     */
    public function supports($name)
    {
    }
}

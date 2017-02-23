<?php

namespace Webtown\DeployerRecipesBundle\Template;

/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 16:52
 */
class TemplateManager
{
    /**
     * @var array|TemplateInterface[]
     */
    protected $templates = [];

    public function getTemplate($name)
    {
        if (!array_key_exists($name, $this->templates)) {
            throw new \Exception(sprintf(
                'The `%s` template doesn\'t exist. Available templates: `%s`',
                $name,
                implode('`, `', $this->getAvailableTemplates())
            ));
        }

        return $this->templates[$name];
    }

    /**
     * @param array|TemplateInterface[] $templates
     */
    public function setTemplates($templates)
    {
        foreach ($templates as $template) {
            $this->addTemplate($template);
        }
    }

    public function addTemplate(TemplateInterface $template)
    {
        $this->templates[$template->getName()] = $template;
    }

    public function getAvailableTemplates()
    {
        return array_keys($this->templates);
    }
}

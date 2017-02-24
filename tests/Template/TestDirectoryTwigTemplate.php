<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:37
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Filesystem\Filesystem;
use Webtown\DeployerRecipesBundle\Template\AbstractDirectoryTwigTemplate;

class TestDirectoryTwigTemplate extends AbstractDirectoryTwigTemplate
{
    /**
     * @var string
     */
    protected $type;

    /**
     * TestDirectoryTwigTemplate constructor.
     * @param string $type
     * @param string $rootDir
     * @param EngineInterface $templating
     * @param Filesystem $filesystem
     */
    public function __construct($type, $rootDir, EngineInterface $templating, Filesystem $filesystem)
    {
        $this->type = $type;

        parent::__construct($rootDir, $templating, $filesystem);
    }

    public function getDirectory()
    {
        return implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            '..',
            'Resources',
            'Template',
            $this->type,
        ]);
    }

    /**
     * @return array|Question[]
     */
    protected function getParameterQuestions()
    {
        return [];
    }

    public function getTemplateParameters()
    {
        return ['name' => 'Test Elek'];
    }

    public function getName()
    {
        return 'test';
    }
}

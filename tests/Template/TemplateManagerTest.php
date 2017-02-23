<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.17.
 * Time: 16:10
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Webtown\DeployerRecipesBundle\Template\TemplateInterface;
use Webtown\DeployerRecipesBundle\Template\TemplateManager;

class TemplateManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $templates
     * @param string $name
     * @param mixed $result
     *
     * @dataProvider getGetTemplates
     */
    public function testGetTemplate($templates, $name, $result)
    {
        $manager = new TemplateManager();
        $manager->setTemplates($templates);

        if ($result instanceof \Exception) {
            $this->expectException(get_class($result));
        }

        $response = $manager->getTemplate($name);

        if (!$result instanceof \Exception) {
            $this->assertEquals($result, $response);
        }
    }

    public function getGetTemplates()
    {
        $testTemplate = new TestDirectoryTwigTemplate('', '', new TestTemplating(), new DummyFilesystem());

        return [
            [[], 'test', new \Exception()],
            [[clone $testTemplate], 'foo', new \Exception()],
            [[$testTemplate], 'test', $testTemplate],
        ];
    }

    /**
     * @param array $templates
     * @param array|string[] $result
     *
     * @dataProvider getAvailableTemplates
     */
    public function testGetAvailableTemplates($templates, $result)
    {
        $manager = new TemplateManager();
        $manager->setTemplates($templates);

        $response = $manager->getAvailableTemplates();

        $this->assertEquals($result, $response);
    }

    public function getAvailableTemplates()
    {
        $testTemplate = new TestDirectoryTwigTemplate('', '', new TestTemplating(), new DummyFilesystem());

        return [
            [[], []],
            [[clone $testTemplate], ['test']],
        ];
    }
}

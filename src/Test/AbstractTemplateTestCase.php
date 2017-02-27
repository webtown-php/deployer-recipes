<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.24.
 * Time: 15:42
 */

namespace Webtown\DeployerRecipesBundle\Test;

use Mockery as m;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webtown\DeployerRecipesBundle\Template\AbstractDirectoryTwigTemplate;

/**
 * Class AbstractTemplateTestCase
 * You can make tests.
 *
 * @todo (Chris) Ide még kellene egy leírás a használatról. Sőt, inkább a README-be.
 *
 * @package Webtown\DeployerRecipesBundle\Test
 */
class AbstractTemplateTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DummyFilesystem
     */
    protected $filesystem;

    public function tearDown()
    {
        m::close();
    }

    protected function buildTemplateObject($class, $testDirectory)
    {
        $twig = new \Twig_Environment(new \Twig_Loader_Array());
        // Nem nézzük meg, hogy valójában léteznek-e a fájlok, mivel léteznek.
        $this->filesystem = new DummyFilesystem();

        return new $class($testDirectory . DIRECTORY_SEPARATOR . 'app', $twig, $this->filesystem);
    }

    protected function buildCommand($template, $parameters, $name = 'test:command')
    {
        $command = new Command($name);

        $questionResponses = [];
        $reflMethod = new \ReflectionMethod(get_class($template), 'getParameterQuestions');
        $reflMethod->setAccessible(true);
        /** @var Question[] $questions */
        $questions = $reflMethod->invokeArgs($template, []);
        foreach ($parameters as $key => $value) {
            $this->assertTrue(
                array_key_exists($key, $questions),
                sprintf('The `%s` parameter key has no question! Existing questions: `%s`', $key, implode('`, `', array_keys($questions)))
            );
            /** @var Question $question */
            $question = $questions[$key];
            $questionResponses[$question->getQuestion()] = $value;
        }
        $questionHelperMock = m::mock(QuestionHelper::class, [
            'getName' => 'question',
            'setHelperSet' => null,
        ]);
        $questionHelperMock
            ->shouldReceive('ask')
            ->atLeast()
            ->times(count($questionResponses))
            ->andReturnUsing(function ($input, $output, $question) use ($questionResponses) {
                /** @var Question $question */
                if (array_key_exists($question->getQuestion(), $questionResponses)) {
                    return $questionResponses[$question->getQuestion()];
                    // Ha arra kérdez rá, hogy felülírja-e a létező teszt fájlokat, akkor azt leokézzuk.
                    // @todo (Chris) Az a baj ezzel a megoldással, ha vki majd hozzányúl a teszthez és rosszul, akkor ez simán felülírja a "tesztfájlokat", így végül "önmagával" fogja összehasonlítani. Lehet, hogy inkább false-t kellene visszaadni, és a ".tpl"-eket levágni a végekről összehasonlításnál.
                } elseif (strpos($question->getQuestion(), 'Do you want override it?')!==false) {
                    return true;
                }

                throw new \Exception(sprintf('Unknown response for the next question: `%s`', $question->getQuestion()));
            })
        ;
        $command->setHelperSet(new HelperSet(['question' => $questionHelperMock]));

        return $command;
    }

    protected function runTestBuild($class, $parameters, $testDirectory)
    {
        /** @var AbstractDirectoryTwigTemplate $template */
        $template = $this->buildTemplateObject($class, $testDirectory);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command = $this->buildCommand($template, $parameters);
        $template->build($input, $output, $command);

        /** @var SplFileInfo[] $resultFiles */
        $resultFiles = Finder::create()->files()->in($testDirectory);
        $responseFiles = $this->filesystem->getDumpedFiles();
        $this->assertEquals(count($responseFiles), count($resultFiles));
        foreach ($resultFiles as $resultFile) {
            $this->assertTrue(
                array_key_exists(static::getRealPath($resultFile->getPathname()), $responseFiles),
                sprintf('The `%s` file didn\'t created! Created files: `%s`', static::getRealPath($resultFile->getPathname()), implode('`, `', array_keys($responseFiles)))
            );
            $this->assertEquals(
                $resultFile->getContents(),
                $responseFiles[static::getRealPath($resultFile->getPathname())],
                sprintf('The result of `%s` file is different.', static::getRealPath($resultFile->getPathname()))
            );
        }
    }

    /**
     * The `realpath()` function is not the best solution for test.
     *
     * @param $filePath
     * @return string
     * @throws \Exception
     */
    public static function getRealPath($filePath)
    {
        $path = [];
        foreach(explode('/', $filePath) as $part) {
            // ignore parts that have no value
            if (empty($part) || $part === '.') continue;

            if ($part !== '..') {
                // cool, we found a new part
                array_push($path, $part);
            }
            else if (count($path) > 0) {
                // going back up? sure
                array_pop($path);
            } else {
                // now, here we don't like
                throw new \Exception('Climbing above the root is not permitted.');
            }
        }
        $fullPath = implode('/', $path);

        return ($filePath[0] == '/') ? '/' . $fullPath : $fullPath;
    }
}

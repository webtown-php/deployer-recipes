<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 19:33
 */

namespace Tests\Webtown\DeployerRecipesBundle\Template;

use Mockery as m;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\TemplateReferenceInterface;
use Webtown\DeployerRecipesBundle\Template\AbstractDirectoryTwigTemplate;

class AbstractDirectoryTwigTemplateTest extends \PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    /**
     * @param string           $type
     * @param string           $rootDir
     * @param array            $existingFiles     Ezek a fájlok léteznek, tehát a $filesystem->exists() true-val fog visszatérni
     * @param array            $questionResponses A megadott kérdésekre a megadott válasszal tér vissza.
     * @param array|\Exception $outputs           Ilyen command kimenetet várunk
     * @param array            $files             Ezek a fájlok jönnek létre, a megadott tartalommal. A kulcs a fájl elérési útja, az érték a fájl tartalma.
     *
     * @dataProvider getTemplates
     */
    public function testBuild($type, $rootDir, $existingFiles, $questionResponses, $outputs, $files)
    {
        $templating = new TestTemplating();
        $filesystem = new DummyFilesystem($existingFiles);
        $template = new TestDirectoryTwigTemplate($type, $rootDir, $templating, $filesystem);
        $input = new ArrayInput([]);
        $output = new BufferedOutput();
        $command = new Command('test:command');
        $questionHelperMock = m::mock(QuestionHelper::class, [
            'getName' => 'question',
            'setHelperSet' => null,
        ]);
        $questionHelperMock
            ->shouldReceive('ask')
            ->with(
                m::on(function($input) {
                    return true;
                }),
                m::on(function ($output) {
                    return true;
                }),
                m::on(function($question) use ($questionResponses) {
                    /** @var ConfirmationQuestion $question */
                    return array_key_exists($question->getQuestion(), $questionResponses);
                })
            )
            ->times(count($questionResponses))
            ->andReturnUsing(function ($input, $output, $question) use ($questionResponses) {
                /** @var ConfirmationQuestion $question */
                return $questionResponses[$question->getQuestion()];
            })
        ;
        $command->setHelperSet(new HelperSet(['question' => $questionHelperMock]));

        if ($outputs instanceof \Exception) {
            $this->expectException(get_class($outputs));
        }

        $template->build($input, $output, $command);

        if (!$outputs instanceof \Exception) {
            $this->assertEquals($outputs, explode("\n", trim($output->fetch())));
            $this->assertEquals($files, $filesystem->getDumpedFiles());
        }
    }

    public function getTemplates()
    {
        $deployPhpContent = <<<EOS
<?php

set('name', 'Test Elek');

EOS;
        $serversYmlContent = <<<EOS
# servers.yml
prod:
    owner: Test Elek

EOS;

        return [
            // TEST - exception
            [
                'test', // $type
                '',      // $rootDir
                [],      // $existingFiles
                [],      // $questionResponses
                new \Exception(),    // $outputs
                []       // $files
            ],
            // TEST0
            [
                'test0', // $type
                '',      // $rootDir
                [],      // $existingFiles
                [],      // $questionResponses
                [''],    // $outputs
                []       // $files
            ],
            // TEST1
            [
                'test1',                             // $type
                '',                                  // $rootDir
                [],                                  // $existingFiles
                [],                                  // $questionResponses
                ['Create the /deploy.php file.'],    // $outputs
                ['/deploy.php' => $deployPhpContent] // $files
            ],
            [
                'test1',
                '/test/root/dir/',
                [],
                [],
                ['Create the /test/root/dir/deploy.php file.'],
                ['/test/root/dir/deploy.php' => $deployPhpContent]
            ],
            [
                'test1',                              // $type
                '',                                   // $rootDir
                ['/deploy.php'],                      // $existingFiles
                ['The `/deploy.php` file exists! Do you want override it? (y/N)' => true], // $questionResponses
                ['Create the /deploy.php file.'],     // $outputs
                ['/deploy.php' => $deployPhpContent]  // $files
            ],
            [
                'test1',                                 // $type
                '',                                      // $rootDir
                ['/deploy.php'],                         // $existingFiles
                ['The `/deploy.php` file exists! Do you want override it? (y/N)' => false], // $questionResponses
                ['Create the /deploy.php.tmp file.'],    // $outputs
                ['/deploy.php.tmp' => $deployPhpContent] // $files
            ],
            // TEST2
            [
                'test2',
                '',
                [],
                [],
                [
                    'Create the /deploy.php file.',
                    'Create the /app/config/Deployer/servers.yml file.',
                ],
                [
                    '/deploy.php' => $deployPhpContent,
                    '/app/config/Deployer/servers.yml' => $serversYmlContent,
                ]
            ],
            [
                'test2',
                '/test/root/dir/',
                [],
                [],
                [
                    'Create the /test/root/dir/deploy.php file.',
                    'Create the /test/root/dir/app/config/Deployer/servers.yml file.',
                ],
                [
                    '/test/root/dir/deploy.php' => $deployPhpContent,
                    '/test/root/dir/app/config/Deployer/servers.yml' => $serversYmlContent,
                ]
            ],
            [
                'test2',
                '',
                ['/deploy.php'],
                ['The `/deploy.php` file exists! Do you want override it? (y/N)' => true], // $questionResponses
                [
                    'Create the /deploy.php file.',
                    'Create the /app/config/Deployer/servers.yml file.',
                ],
                [
                    '/deploy.php' => $deployPhpContent,
                    '/app/config/Deployer/servers.yml' => $serversYmlContent,
                ]
            ],
            [
                'test2',
                '',
                ['/deploy.php'],
                ['The `/deploy.php` file exists! Do you want override it? (y/N)' => false], // $questionResponses
                [
                    'Create the /deploy.php.tmp file.',
                    'Create the /app/config/Deployer/servers.yml file.',
                ],
                [
                    '/deploy.php.tmp' => $deployPhpContent,
                    '/app/config/Deployer/servers.yml' => $serversYmlContent,
                ]
            ],
        ];
    }
}

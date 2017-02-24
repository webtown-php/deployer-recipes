<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 18:23
 */

namespace Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Templating\EngineInterface;

abstract class AbstractDirectoryTwigTemplate implements TemplateInterface
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var array
     */
    protected $parameters = [];

    public function __construct($rootDir, \Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    public function build(InputInterface $input, OutputInterface $output, Command $command)
    {
        $this->askParameters($input, $output, $command);

        /** @var SplFileInfo[] $files */
        $files = $this->getFiles($input, $output);

        foreach ($files as $file) {
            $this->buildFile($file, $input, $output, $command);
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return \Symfony\Component\Finder\SplFileInfo[]
     * @throws \Exception
     */
    protected function getFiles(InputInterface $input, OutputInterface $output)
    {
        $directory = $this->getDirectory();
        if (!$this->filesystem->exists($directory)) {
            // @todo (Chris) Ide inkább egy $output-tal megformázott szép üzenet kellene.
            throw new \Exception('Invalid directory: ' . $directory);
        }

        return Finder::create()->files()->in($directory);
    }

    protected function buildFile(SplFileInfo $file, InputInterface $input, OutputInterface $output, Command $command)
    {
        $helper = $command->getHelper('question');
        $targetPath = implode(DIRECTORY_SEPARATOR, [
            $this->rootDir,
            $file->getRelativePathname()
        ]);
        if ($this->filesystem->exists($targetPath)) {
            $question = new ConfirmationQuestion(sprintf('The `%s` file exists! Do you want override it? (y/N)', $targetPath), false);
            if (!$helper->ask($input, $output, $question)) {
                $targetPath .= '.tmp';
            }
        }

        $template = $this->twig->createTemplate($file->getContents());
        $newContent = $template->render(array_merge($this->parameters, $this->getTemplateParameters()));
        $this->filesystem->dumpFile($targetPath, $newContent);
        $output->writeln(sprintf('Create the <info>%s</info> file.', $targetPath));
    }


    protected function askParameters(InputInterface $input, OutputInterface $output, Command $command)
    {
        /** @var QuestionHelper $helper */
        $helper = $command->getHelper('question');
        /** @var array|Question[] $questions */
        $questions = $this->getParameterQuestions();

        foreach ($questions as $parameterName => $question) {
            $this->parameters[$parameterName] = $helper->ask($input, $output, $question);
        }
    }

    /**
     * @return array|Question[]
     */
    abstract protected function getParameterQuestions();

    abstract public function getDirectory();

    abstract public function getTemplateParameters();
}

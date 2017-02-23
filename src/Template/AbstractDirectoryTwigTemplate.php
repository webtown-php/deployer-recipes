<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 18:23
 */

namespace Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct($rootDir, EngineInterface $templating, Filesystem $filesystem)
    {
        $this->rootDir = rtrim($rootDir, DIRECTORY_SEPARATOR);
        $this->templating = $templating;
        $this->filesystem = $filesystem;
    }

    public function build(InputInterface $input, OutputInterface $output, Command $command)
    {
        $directory = $this->getDirectory();

        if (!$this->filesystem->exists($directory)) {
            // @todo (Chris) Ide inkább egy $output-tal megformázott szép üzenet kellene.
            throw new \Exception('Invalid directory: ' . $directory);
        }

        $helper = $command->getHelper('question');
        /** @var SplFileInfo[] $files */
        $files = Finder::create()->files()->in($directory);

        foreach ($files as $file) {
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

            $newContent = $this->templating->render($file->getPathname(), $this->getTemplateParameters());
            $this->filesystem->dumpFile($targetPath, $newContent);
            $output->writeln(sprintf('Create the <info>%s</info> file.', $targetPath));
        }
    }

    abstract public function getDirectory();

    // @todo (Chris) Itt lehet, hogy át kellene adni az $input, $output, $command hármast, hogy itt lehessen feltenni a kérdéseket
    abstract public function getTemplateParameters();
}

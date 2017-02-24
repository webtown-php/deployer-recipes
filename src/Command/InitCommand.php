<?php
/**
 * Created by IntelliJ IDEA.
 * User: chris
 * Date: 2017.02.16.
 * Time: 16:24
 */

namespace Webtown\DeployerRecipesBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Webtown\DeployerRecipesBundle\Template\TemplateManager;

class InitCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('webtown:deployer:init')
            ->setDescription('Initialize the deployment')
            ->setHelp(<<<EOT
The <info>%command.name%</info> initialize the deployment:

<info>php app/console %command.name% --branch=master --last=a978ec5fa342d88fa71e67f0482c2b33037a4271</info>
EOT
            )
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TemplateManager $templateManager */
        $templateManager = $this->getContainer()->get('webtown_deployer.template_manager');

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Select template:',
            $templateManager->getAvailableTemplates(),
            0
        );
        $question->setErrorMessage('The %s template is invalid.');

        $templateName = $helper->ask($input, $output, $question);

        $template = $templateManager->getTemplate($templateName);
        $template->build($input, $output, $this);
    }
}

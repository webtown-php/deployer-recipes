<?php
/**
 * Created by PhpStorm.
 * User: Gabe
 * Date: 2017.02.09.
 * Time: 12:07
 */

namespace Webtown\DeployerRecipesBundle\Command;

use Swift_Attachment;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SendMailAfterDeploymentCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('webtown:deployment:send-mail')
            ->setDescription('Send notification mails after deployer deployment')
            ->setHelp(<<<EOT
The <info>%command.name%</info> sends email after deployer deployment:

<info>php app/console %command.name% --branch=master --last=a978ec5fa342d88fa71e67f0482c2b33037a4271</info>


EOT
            )
            ->addOption('branch', null, InputOption::VALUE_OPTIONAL, 'Active branch', 'HEAD')
            ->addOption('last', null, InputOption::VALUE_OPTIONAL, 'Last deployment\'s commit hash')
            ->addOption('no-merges', null, InputOption::VALUE_NONE, 'Include merge commits or not')
            ->addOption('mail-pretty', null, InputOption::VALUE_OPTIONAL, 'GIT log command `--pretty` parameter value in mail body', 'oneline')
            ->addOption('attachment-pretty', null, InputOption::VALUE_OPTIONAL, 'GIT log command `--pretty` parameter value in mail attachment', 'oneline')
            ->addOption('from', null, InputOption::VALUE_OPTIONAL, '`From` e-mail address', 'no-reply@webtown.hu')
            ->addOption('to', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '`To` e-mail addresses', ["hgabka@gmail.com"])
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'E-mail subject', 'Deployment success')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bodyOutput = null;
        $attachmentOutput = null;
        if ($input->getOption('last')) {
            $output->writeln('Run git command ...');
            $command = sprintf('git log %s..%s %s --pretty=%s', $input->getOption('last'), $input->getOption('branch'), $input->getOption('no-merges') ? '--no-merges' : '', $input->getOption('mail-pretty')
            );
            $output->writeln("<info>$command</info>");
            // A levéltőrzsbe kerülő lista
            exec($command, $bodyOutput);

            // Csatolmányként kerül a levélbe
            if ($input->getOption('attachment-pretty')) {
                $output->writeln('Run git command (attachment) ...');
                $command = sprintf('git log %s..%s %s --pretty=%s', $input->getOption('last'), $input->getOption('branch'), $input->getOption('no-merges') ? '--no-merges' : '', $input->getOption('attachment-pretty')
                );
                $output->writeln("<info>$command</info>");
                exec($command, $attachmentOutput);
            }
        }

        $this->sendMail(
            $input->getOption('from'),
            $input->getOption('to'),
            $input->getOption('subject'),
            'WebtownDeployerRecipesBundle:Mail:afterDeployment.html.twig',
            $bodyOutput,
            $attachmentOutput
        );

        $output->writeln('Send mails');
    }

    /**
     * Az adatok alapján összeállítja és kiküldi a levelet.
     *
     * @param string       $from
     * @param string|array $to
     * @param string       $subject
     * @param string       $template
     * @param string|array $bodyOutput
     * @param string|array $attachmentOutput
     */
    protected function sendMail($from, $to, $subject, $template, $bodyOutput, $attachmentOutput = null)
    {
        if (is_array($bodyOutput)) {
            $bodyOutput = implode("\n", $bodyOutput);
        }

        $body = $this->getContainer()->get('templating')->render($template, [ 'output' => $bodyOutput]);

        $mail = Swift_Message::newInstance();

        $mail
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body, 'text/html')
        ;

        if ($attachmentOutput) {
            if (is_array($attachmentOutput)) {
                $attachmentOutput = implode("\n", $attachmentOutput);
            }

            $attachment = Swift_Attachment::newInstance($attachmentOutput, 'commits.txt', 'text/plain');
            $mail->attach($attachment);
        }

        $this->getContainer()->get('mailer')->send($mail);
    }
}

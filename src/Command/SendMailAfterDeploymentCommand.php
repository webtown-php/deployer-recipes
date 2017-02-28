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

<info>%command.full_name% --from=<comment>deploy@example.com</comment> --to=<comment>boss@example.com</comment> --to=<comment>developers@example.com</comment> --subject=<comment>"Deployment success"</comment> --base64-log=<comment>"bGtqc2QzNGxrczQzazJsajJrbDIgVGVzdCBjb21taXQgMg0KazJubGtmZHNsa2ozNDJsa2RqdyBUZXN0IGNvbW1pdCAx"</comment></info>
EOT
            )
            ->addOption('from', null, InputOption::VALUE_REQUIRED, '`From` e-mail address')
            ->addOption('to', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, '`To` e-mail addresses')
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'E-mail subject', 'Deployment success')
            ->addOption('base64-log', null, InputOption::VALUE_REQUIRED, 'Base64 encoded GIT log')
            ->addOption('base64-log-attachment', null, InputOption::VALUE_OPTIONAL, 'Base64 encoded GIT log for attachment')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logForBody = $input->getOption('base64-log')
            ? base64_decode($input->getOption('base64-log'))
            : null;
        $logForAttachment = $input->getOption('base64-log-attachment')
            ? base64_decode($input->getOption('base64-log-attachment'))
            : null;

        $n = $this->sendMail(
            $input->getOption('from'),
            $input->getOption('to'),
            $input->getOption('subject'),
            'WebtownDeployerRecipesBundle:Mail:afterDeployment.html.twig',
            $logForBody,
            $logForAttachment
        );

        $output->writeln('Send mails: ' . $n);
    }

    /**
     * Az adatok alapján összeállítja és kiküldi a levelet.
     *
     * @param string       $from
     * @param string|array $to
     * @param string       $subject
     * @param string       $template
     * @param string|array $logForBody
     * @param string|array $logForAttachment
     * @return int
     */
    protected function sendMail($from, $to, $subject, $template, $logForBody, $logForAttachment = null)
    {
        if (is_array($logForBody)) {
            $logForBody = implode("\n", $logForBody);
        }

        $body = $this->getContainer()->get('templating')->render($template, [ 'log_text' => $logForBody]);

        $mail = Swift_Message::newInstance();

        $mail
            ->setFrom($from)
            ->setTo($to)
            ->setSubject($subject)
            ->setBody($body, 'text/html')
        ;

        if ($logForAttachment) {
            if (is_array($logForAttachment)) {
                $logForAttachment = implode("\n", $logForAttachment);
            }

            $attachment = Swift_Attachment::newInstance($logForAttachment, 'commits.txt', 'text/plain');
            $mail->attach($attachment);
        }

        return $this->getContainer()->get('mailer')->send($mail);
    }
}

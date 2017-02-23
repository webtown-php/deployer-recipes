<?php

namespace Webtown\DeployerRecipesBundle\Template;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

interface TemplateInterface
{
    public function build(InputInterface $input, OutputInterface $output, Command $command);
    public function getName();
}

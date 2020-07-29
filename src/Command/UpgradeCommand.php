<?php

namespace Samwilson\Twyne\Command;

use Samwilson\Twyne\Database;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends Command
{

    /**
     * Configures the current command.
     */
    protected function configure()
    {
        parent::configure();
        $this->setName('upgrade');
        $this->setDescription('Upgrade or install this application');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int Null or 0 if everything went fine, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Database::getInstance()->install();
        return 0;
    }
}

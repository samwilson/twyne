<?php

namespace App\Command;

use App\Repository\UserRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TwyneReset2FACommand extends Command
{
    protected static $defaultName = 'twyne:reset-2fa';

    /** @var SymfonyStyle */
    private $io;

    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
    }

    protected function configure()
    {
        $this
            ->setDescription("Remove a user's two-factor authentication secret (to force them to re-register one)")
            ->addOption('username', 'u', InputOption::VALUE_REQUIRED, 'Username')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $username = $input->getOption('username');
        if (!$username) {
            $this->io->warning('Please set --username');
            return Command::FAILURE;
        }
        $user = $this->userRepository->findOneBy(['username' => $username]);
        if (!$user) {
            $this->io->warning("User '$username' not found.");
            return Command::FAILURE;
        }

        if (!$user->getTwoFASecret()) {
            $this->io->warning("User '$username' does not have a two-factor authentication secret stored.");
            return Command::FAILURE;
        }

        $user->setTwoFASecret(null);
        $this->userRepository->save($user);
        $this->io->success("Two-factor authentication secret removed for user '$username'.");
        return Command::SUCCESS;
    }
}

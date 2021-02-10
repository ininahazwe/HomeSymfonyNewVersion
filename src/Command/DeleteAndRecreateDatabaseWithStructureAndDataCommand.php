<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Liip\ImagineBundle\Exception\LogicException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteAndRecreateDatabaseWithStructureAndDataCommand extends Command
{
    private $entityManager;

    protected static $defaultName = 'app:clean-db';

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this->setDescription('Supprime et recrée la bd avec sa structure');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->section("Suppression de la BD puis création d'une nouvelle avec structure et données");

        $this->runSymfonyCommand($input, $output, 'doctrine:database:drop', true);

        $this->runSymfonyCommand($input, $output, 'doctrine:database:create');

        $this->runSymfonyCommand($input, $output, 'doctrine:migrations:migrate');

        $this->createRememberMeTokenTable();

        $io->success('RAS => BD toute propre avec ses data.');

        return Command::SUCCESS;
    }

    private function runSymfonyCommand(InputInterface $input, OutputInterface $output, string $command, bool $forceOption = false): void
    {
        $application = $this->getApplication();

        if(!$application){
            throw new LogicException("No application: (");
        }

        $command = $application->find($command);

        if($forceOption){
            $input = new ArrayInput([
                '--force' => true
            ]);
        }
        $input->setInteractive(false);

        $command->run($input, $output);
    }

    private function createRememberMeTokenTable(): void
    {
        $sqlQuery = "CREATE TABLE `rememberme_token` (
            `series`   char(88)     UNIQUE PRIMARY KEY NOT NULL,
            `value`    varchar(88)  NOT NULL,
            `lastUsed` datetime     NOT NULL,
            `class`    varchar(100) NOT NULL,
            `username` varchar(200) NOT NULL            
        );";

        $this->entityManager->getConnection()->executeQuery($sqlQuery);
    }
}

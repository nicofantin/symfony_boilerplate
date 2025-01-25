<?php

namespace App\Command;

use App\Service\TaskFileService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:task',
    description: 'Gestion des tâches via la console',
)]
class TaskCommand extends Command
{
    private TaskFileService $taskFileService;

    public function __construct(TaskFileService $taskFileService)
    {
        $this->taskFileService = $taskFileService;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action à effectuer (create, update, list, get, delete)')
            ->addArgument('id', InputArgument::OPTIONAL, 'ID de la tâche')
            ->addArgument('title', InputArgument::OPTIONAL, 'Titre de la tâche')
            ->addArgument('description', InputArgument::OPTIONAL, 'Description de la tâche');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');
        $id = $input->getArgument('id');
        $title = $input->getArgument('title');
        $description = $input->getArgument('description');

        try {
            switch ($action) {
                case 'create':
                    $this->taskFileService->createTask($title, $description);
                    $output->writeln('<info>Tâche créée avec succès.</info>');
                    break;

                case 'update':
                    $this->taskFileService->updateTask($id, $title, $description);
                    $output->writeln('<info>Tâche mise à jour avec succès.</info>');
                    break;

                case 'list':
                    $tasks = $this->taskFileService->listTasks();
                    foreach ($tasks as $task) {
                        $output->writeln(sprintf('<info>ID: %s, Titre: %s</info>', $task['id'], $task['title']));
                    }
                    break;

                case 'get':
                    $task = $this->taskFileService->getTask($id);
                    $output->writeln(sprintf('<info>ID: %s</info>', $task['id']));
                    $output->writeln(sprintf('<info>Titre: %s</info>', $task['title']));
                    $output->writeln(sprintf('<info>Description: %s</info>', $task['description']));
                    break;

                case 'delete':
                    $this->taskFileService->deleteTask($id);
                    $output->writeln('<info>Tâche supprimée avec succès.</info>');
                    break;

                default:
                    $output->writeln('<error>Action inconnue.</error>');
            }
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
        }

        return Command::SUCCESS;
    }
}

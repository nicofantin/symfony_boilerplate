<?php

namespace App\Service;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;

class TaskFileService
{
    private string $taskDirectory;
    private Filesystem $filesystem;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir = __DIR__ . '/../../public/tasks/')
    {
        $this->entityManager = $entityManager;
        $this->taskDirectory = $projectDir;
        $this->filesystem = new Filesystem();

        // Vérifie que le répertoire existe, sinon le crée
        if (!$this->filesystem->exists($this->taskDirectory)) {
            $this->filesystem->mkdir($this->taskDirectory);
        }
    }

    // a. Ajouter une tâche
    public function createTask(string $title, string $description): void
    {
        // Créer un fichier
        $id = uniqid(); // Génère un ID unique
        $filePath = $this->taskDirectory . $id . '.txt';

        $content = sprintf("Titre : %s\nDescription : %s\nDate de création : %s", $title, $description, date('Y-m-d H:i:s'));
        $this->filesystem->dumpFile($filePath, $content);

        // Persister la tâche dans la base de données
        $task = new Task();
        $task->setName($title);
        $task->setDescription($description);
        $task->setCreatedAt(new \DateTimeImmutable());
        $this->entityManager->persist($task);
        $this->entityManager->flush();
    }

    // b. Modifier une tâche
    public function updateTask(string $id, string $title, string $description): void
    {
        // Mettre à jour le fichier
        $filePath = $this->taskDirectory . $id . '.txt';

        if (!$this->filesystem->exists($filePath)) {
            throw new FileNotFoundException(sprintf("Le fichier pour la tâche %s n'existe pas.", $id));
        }

        $content = sprintf("Titre : %s\nDescription : %s\nDate de mise à jour : %s", $title, $description, date('Y-m-d H:i:s'));
        $this->filesystem->dumpFile($filePath, $content);

        // Mettre à jour la base de données
        $task = $this->entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            throw new \InvalidArgumentException(sprintf("La tâche avec l'ID %s n'existe pas.", $id));
        }

        $task->setTitle($title);
        $task->setDescription($description);
        $task->setUpdatedAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }

    // c. Lister toutes les tâches
    public function listTasks(): array
    {
        // Lire depuis la base de données
        $tasks = $this->entityManager->getRepository(Task::class)->findAll();

        // Retourner un tableau de tâches
        return $tasks;
    }

    // d. Afficher les détails d’une tâche
    public function getTask(string $id): array
    {
        // Récupérer les détails depuis le fichier
        $filePath = $this->taskDirectory . $id . '.txt';

        if (!$this->filesystem->exists($filePath)) {
            throw new FileNotFoundException(sprintf("Le fichier pour la tâche %s n'existe pas.", $id));
        }

        $content = file_get_contents($filePath);

        preg_match('/Titre : (.+)/', $content, $titleMatch);
        preg_match('/Description : (.+)/', $content, $descriptionMatch);

        // Récupérer depuis la base de données
        $task = $this->entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            throw new \InvalidArgumentException(sprintf("La tâche avec l'ID %s n'existe pas dans la base de données.", $id));
        }

        return [
            'id' => $id,
            'title' => $titleMatch[1] ?? 'Sans titre',
            'description' => $descriptionMatch[1] ?? 'Pas de description',
            'database' => $task,
        ];
    }

    // e. Supprimer une tâche
    public function deleteTask(string $id): void
    {
        // Supprimer le fichier
        $filePath = $this->taskDirectory . $id . '.txt';

        if (!$this->filesystem->exists($filePath)) {
            throw new FileNotFoundException(sprintf("Le fichier pour la tâche %s n'existe pas.", $id));
        }

        $this->filesystem->remove($filePath);

        // Supprimer de la base de données
        $task = $this->entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            throw new \InvalidArgumentException(sprintf("La tâche avec l'ID %s n'existe pas dans la base de données.", $id));
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();
    }
}

<?php

namespace App\Service;

use App\Entity\Task;

class TaskService
{
    private const EDITABLE_DAYS_LIMIT = 7;

    /**
     * Vérifie si une tâche peut être modifiée en fonction de la date de création.
     */
    public function canEdit(Task $task): bool
    {
        $createdAt = $task->getCreatedAt();

        if (!$createdAt) {
            return false; // Si la date de création n'est pas définie, on ne peut pas modifier.
        }

        $now = new \DateTimeImmutable();
        $difference = $now->diff($createdAt);

        return $difference->days < self::EDITABLE_DAYS_LIMIT;
    }
}

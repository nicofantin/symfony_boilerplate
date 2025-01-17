<?php

namespace App\Security\Voter;

use App\Entity\Task;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class TaskVoter extends Voter
{
    public const EDIT = 'TASK_EDIT';
    public const VIEW = 'TASK_VIEW';
    public const DELETE = 'TASK_DELETE';
    public const VIEW_AUTHOR = 'TASK_VIEW_AUTHOR';

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Vérifie que l'attribut est supporté et que le sujet est une instance de Task
        return in_array($attribute, [self::EDIT, self::VIEW, self::DELETE])
            && $subject instanceof Task;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // Si l'utilisateur est anonyme, on refuse l'accès
        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Task $task */
        $task = $subject;

        // Les administrateurs ont tous les droits
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Logique pour chaque permission
        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($task, $user);
            case self::VIEW:
                return $this->canView($task, $user);
            case self::DELETE:
                return $this->canDelete($task, $user);
        }

        return false;
    }

    private function canEdit(Task $task, UserInterface $user): bool
    {
        // Seul l'auteur peut modifier la tâche
        return $task->getAuthor() === $user;
    }

    private function canView(Task $task, UserInterface $user): bool
    {
        // Seul l'auteur peut voir la tâche
        return $task->getAuthor() === $user;
    }

    private function canDelete(Task $task, UserInterface $user): bool
    {
        // Suppression interdite pour tous les utilisateurs
        return false;
    }

    private function canViewAuthor(UserInterface $user): bool
    {
        // Seuls les administrateurs peuvent voir le champ "author"
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

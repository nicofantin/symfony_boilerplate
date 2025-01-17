<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tasks')]
class TaskController extends AbstractController
{
    #[Route('/index', name: 'task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        // Affiche la liste des tâches
        $tasks = $taskRepository->findAll();

        return $this->render('task/index.html.twig', [
            'tasks' => $tasks,
        ]);
    }

    #[Route('/create', name: 'task_create', methods: ['GET', 'POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Crée une nouvelle tâche
        $task = new Task();
        $task->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/view/{id}', name: 'task_view', methods: ['GET'])]
    public function view(Task $task): Response
    {
        // Vérification des permissions avec le voter
        if (!$this->isGranted('TASK_VIEW', $task)) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de voir cette tâche.');
            return $this->redirectToRoute('task_index');
        }
    
        // Affiche les détails d'une tâche
        return $this->render('task/view.html.twig', [
            'task' => $task,
        ]);
    }
    

    #[Route('/edit/{id}', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification des permissions
        if (!$this->isGranted('TASK_EDIT', $task)) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de modifier cette tâche.');
            return $this->redirectToRoute('task_index');
        }
    
        // Modifie une tâche existante
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdateAt(new \DateTimeImmutable());
            $entityManager->flush();
    
            $this->addFlash('success', 'Tâche modifiée avec succès.');
            return $this->redirectToRoute('task_index');
        }
    
        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }
    

    #[Route('/delete/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task, EntityManagerInterface $entityManager): Response
    {
        // Vérification des permissions
        if (!$this->isGranted('TASK_DELETE', $task)) {
            $this->addFlash('error', 'Vous n\'avez pas l\'autorisation de supprimer cette tâche.');
            return $this->redirectToRoute('task_index');
        }
    
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $entityManager->remove($task);
            $entityManager->flush();
            $this->addFlash('success', 'Tâche supprimée avec succès.');
        }
    
        return $this->redirectToRoute('task_index');
    }
    
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
// Suggested code may be subject to a license. Learn more: ~LicenseLog:4097629403.
use App\Form\UserType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;



class UserController extends AbstractController
{
    #[Route('/user/create', name: 'user_create', methods: ['GET', 'POST'])]
    public function createUser(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur créé avec succès.');

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/list', name: 'user_list', methods: ['GET'])]
    public function listUsers(EntityManagerInterface $entityManager): Response
    {
        // Récupérer tous les utilisateurs depuis la base de données
        $users = $entityManager->getRepository(User::class)->findAll();

        // Rendre un template pour afficher la liste des utilisateurs
        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/', name: 'login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Récupère l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();

        // Récupère le dernier email saisi par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/redirect', name: 'redirect_after_login', methods: ['GET'])]
    public function redirectAfterLogin(): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        // Redirection basée sur les rôles
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('user_list');
        }

        return $this->redirectToRoute('task_list'); // Remplacez 'task_list' par la route de la liste des tâches
    }
    
}

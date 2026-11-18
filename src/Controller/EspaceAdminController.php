<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\MongoLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/espace-admin')]
#[IsGranted('ROLE_ADMIN')]
class EspaceAdminController extends AbstractController
{
    public function __construct(
        private MongoLogService $mongoLogService,
    ) {
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    #[Route('', name: 'app_espace_admin')]
    public function index(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        // --- Onglet Employés ---
        $allUsers = $userRepository->findAll();

        $employees = array_filter($allUsers, function (User $user): bool {
            return in_array('ROLE_EMPLOYEE', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles());
        });

        // --- Onglet Logs (MongoDB) ---
        $filterPseudo = $request->query->get('pseudo');
        $filterPersonnageId = $request->query->get('personnage_id');
        $filterAction = $request->query->get('action');
        $filterDateDebut = $request->query->get('date_debut');
        $filterDateFin = $request->query->get('date_fin');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 50;
        $offset = ($page - 1) * $limit;

        // Convertir les dates si présentes
        $dateDebut = null;
        if ($filterDateDebut) {
            try {
                $dateDebut = new \DateTimeImmutable($filterDateDebut . ' 00:00:00');
            } catch (\Exception) {
                $dateDebut = null;
            }
        }

        $dateFin = null;
        if ($filterDateFin) {
            try {
                $dateFin = new \DateTimeImmutable($filterDateFin . ' 23:59:59');
            } catch (\Exception) {
                $dateFin = null;
            }
        }

        $personnageId = $filterPersonnageId ? (int) $filterPersonnageId : null;

        $logs = $this->mongoLogService->findLogs(
            pseudo: $filterPseudo ?: null,
            idPersonnage: $personnageId,
            action: $filterAction ?: null,
            dateDebut: $dateDebut,
            dateFin: $dateFin,
            limit: $limit,
            offset: $offset,
        );

        $totalLogs = $this->mongoLogService->countLogs(
            pseudo: $filterPseudo ?: null,
            idPersonnage: $personnageId,
            action: $filterAction ?: null,
            dateDebut: $dateDebut,
            dateFin: $dateFin,
        );

        $totalPages = max(1, (int) ceil($totalLogs / $limit));

        // Récupérer les actions distinctes pour le filtre dropdown
        $availableActions = $this->mongoLogService->getDistinctActions();

        return $this->render('espace_admin/index.html.twig', [
            // Employés
            'employees' => $employees,
            // Logs
            'mongoAvailable' => $this->mongoLogService->isAvailable(),
            'logs' => $logs,
            'totalLogs' => $totalLogs,
            'availableActions' => $availableActions,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'limit' => $limit,
            // Filtres actuels (pour conserver dans le formulaire)
            'filterPseudo' => $filterPseudo,
            'filterPersonnageId' => $filterPersonnageId,
            'filterAction' => $filterAction,
            'filterDateDebut' => $filterDateDebut,
            'filterDateFin' => $filterDateFin,
        ]);
    }

    // =========================================================================
    // EMPLOYES - Gestion
    // =========================================================================

    #[Route('/employe/creer', name: 'app_admin_employe_create')]
    public function createEmployee(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository,
        ValidatorInterface $validator,
    ): Response {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('employe-create', $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_espace_admin');
            }

            $pseudo = trim((string) $request->request->get('pseudo', ''));
            $email = trim((string) $request->request->get('email', ''));
            $password = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            // Validation basique
            $errors = [];

            if ($pseudo === '') {
                $errors[] = 'Le pseudo est requis.';
            } elseif (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
                $errors[] = 'Le pseudo doit contenir entre 3 et 50 caractères.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
                $errors[] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.';
            }

            if ($email === '') {
                $errors[] = 'L\'email est requis.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'email n\'est pas valide.';
            }

            if ($password === '') {
                $errors[] = 'Le mot de passe est requis.';
            } else {
                if (strlen($password) < 12) {
                    $errors[] = 'Le mot de passe doit contenir au moins 12 caractères.';
                }
                if (!preg_match('/[A-Z]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
                }
                if (!preg_match('/[a-z]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
                }
                if (!preg_match('/[0-9]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
                }
                if (!preg_match('/[^A-Za-z0-9]/', $password)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
                }
            }

            if ($password !== $passwordConfirm) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('espace_admin/employe_form.html.twig', [
                    'isEdit' => false,
                    'pseudo' => $pseudo,
                    'email' => $email,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            // Récupérer le rôle 'employe'
            $roleEmploye = $roleRepository->findOneBy(['libelle' => 'employe']);
            if ($roleEmploye === null) {
                $this->addFlash('error', 'Le rôle employé n\'existe pas en base de données.');
                return $this->redirectToRoute('app_espace_admin');
            }

            $user = new User();
            $user->setPseudo($pseudo);
            $user->setEmail($email);
            $user->setRole($roleEmploye);

            // Hasher le mot de passe
            $hashedPassword = $passwordHasher->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            // Valider l'entité (contraintes d'unicité, etc.)
            $violations = $validator->validate($user);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                return $this->render('espace_admin/employe_form.html.twig', [
                    'isEdit' => false,
                    'pseudo' => $pseudo,
                    'email' => $email,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $em->persist($user);
            $em->flush();

            // Log dans MongoDB
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $this->mongoLogService->log(
                $currentUser->getId(),
                $currentUser->getPseudo(),
                0,
                '',
                'employe_creation',
                sprintf('Employé "%s" créé par %s', $pseudo, $currentUser->getPseudo())
            );

            $this->addFlash('success', sprintf('L\'employé "%s" a été créé avec succès.', $pseudo));

            return $this->redirectToRoute('app_espace_admin');
        }

        return $this->render('espace_admin/employe_form.html.twig', [
            'isEdit' => false,
            'pseudo' => '',
            'email' => '',
        ]);
    }

    #[Route('/employe/{id}/modifier', name: 'app_admin_employe_edit', requirements: ['id' => '\d+'])]
    public function editEmployee(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator,
    ): Response {
        // Empêcher de modifier soi-même via cette interface
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas modifier votre propre compte depuis cette interface.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Empêcher de modifier un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Les administrateurs ne peuvent pas être modifiés via cette interface.');
            return $this->redirectToRoute('app_espace_admin');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('employe-edit-' . $user->getId(), $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_espace_admin');
            }

            $pseudo = trim((string) $request->request->get('pseudo', ''));
            $email = trim((string) $request->request->get('email', ''));
            $newPassword = (string) $request->request->get('password', '');
            $passwordConfirm = (string) $request->request->get('password_confirm', '');

            // Validation basique
            $errors = [];

            if ($pseudo === '') {
                $errors[] = 'Le pseudo est requis.';
            } elseif (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
                $errors[] = 'Le pseudo doit contenir entre 3 et 50 caractères.';
            } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $pseudo)) {
                $errors[] = 'Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.';
            }

            if ($email === '') {
                $errors[] = 'L\'email est requis.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'L\'email n\'est pas valide.';
            }

            // Le mot de passe est optionnel en édition
            if ($newPassword !== '') {
                if (strlen($newPassword) < 12) {
                    $errors[] = 'Le mot de passe doit contenir au moins 12 caractères.';
                }
                if (!preg_match('/[A-Z]/', $newPassword)) {
                    $errors[] = 'Le mot de passe doit contenir au moins une majuscule.';
                }
                if (!preg_match('/[a-z]/', $newPassword)) {
                    $errors[] = 'Le mot de passe doit contenir au moins une minuscule.';
                }
                if (!preg_match('/[0-9]/', $newPassword)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
                }
                if (!preg_match('/[^A-Za-z0-9]/', $newPassword)) {
                    $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
                }
                if ($newPassword !== $passwordConfirm) {
                    $errors[] = 'Les mots de passe ne correspondent pas.';
                }
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('espace_admin/employe_form.html.twig', [
                    'isEdit' => true,
                    'user' => $user,
                    'pseudo' => $pseudo,
                    'email' => $email,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $user->setPseudo($pseudo);
            $user->setEmail($email);

            // Changer le mot de passe si fourni
            if ($newPassword !== '') {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            }

            // Valider l'entité (contraintes d'unicité, etc.)
            $violations = $validator->validate($user);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash('error', $violation->getMessage());
                }
                return $this->render('espace_admin/employe_form.html.twig', [
                    'isEdit' => true,
                    'user' => $user,
                    'pseudo' => $pseudo,
                    'email' => $email,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $em->flush();

            // Log dans MongoDB
            $this->mongoLogService->log(
                $currentUser->getId(),
                $currentUser->getPseudo(),
                0,
                '',
                'employe_modification',
                sprintf('Employé "%s" modifié par %s', $pseudo, $currentUser->getPseudo())
            );

            $this->addFlash('success', sprintf('L\'employé "%s" a été modifié avec succès.', $pseudo));

            return $this->redirectToRoute('app_espace_admin');
        }

        return $this->render('espace_admin/employe_form.html.twig', [
            'isEdit' => true,
            'user' => $user,
            'pseudo' => $user->getPseudo(),
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/employe/{id}/suspendre', name: 'app_admin_employe_toggle_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleEmployeeStatus(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle-status-' . $user->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Empêcher de se suspendre soi-même
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas suspendre votre propre compte.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Empêcher de suspendre un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Les administrateurs ne peuvent pas être suspendus via cette interface.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Toggle suspendu
        if ($user->isSuspendu()) {
            $user->setSuspendu(false);
            $message = sprintf('L\'employé "%s" a été réactivé.', $user->getPseudo());
            $logAction = 'employe_reactivation';
        } else {
            $user->setSuspendu(true);
            $message = sprintf('L\'employé "%s" a été suspendu.', $user->getPseudo());
            $logAction = 'employe_suspension';
        }

        $em->flush();

        // Log dans MongoDB
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            $logAction,
            $message . ' (par ' . $currentUser->getPseudo() . ')'
        );

        $this->addFlash('success', $message);

        return $this->redirectToRoute('app_espace_admin');
    }

    #[Route('/employe/{id}/supprimer', name: 'app_admin_employe_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteEmployee(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-' . $user->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Empêcher de se supprimer soi-même
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($user->getId() === $currentUser->getId()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('app_espace_admin');
        }

        // Empêcher de supprimer un administrateur
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'Les administrateurs ne peuvent pas être supprimés via cette interface.');
            return $this->redirectToRoute('app_espace_admin');
        }

        $pseudo = $user->getPseudo();
        $userId = $user->getId();

        // Log dans MongoDB avant suppression
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'employe_suppression',
            sprintf('Employé "%s" (id: %d) supprimé par %s', $pseudo, $userId, $currentUser->getPseudo())
        );

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', sprintf('L\'employé "%s" a été supprimé.', $pseudo));

        return $this->redirectToRoute('app_espace_admin');
    }
}

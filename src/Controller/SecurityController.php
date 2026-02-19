<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Service\MailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SecurityController extends AbstractController
{
    #[Route('/connexion', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Si l'utilisateur est deja connecte, rediriger selon son role
        $user = $this->getUser();
        if ($user instanceof User) {
            // Verifier si le compte est suspendu
            if ($user->isSuspendu()) {
                // Invalider la session serait gere par un listener, mais on ajoute un flash
                $this->addFlash('error', 'Votre compte est suspendu. Vous ne pouvez pas vous connecter.');
                return $this->redirectToRoute('app_logout');
            }

            // Verifier si le mot de passe doit etre modifie
            if ($user->isMotDePasseAModifier()) {
                return $this->redirectToRoute('app_change_password');
            }

            return $this->redirectBasedOnRole($user);
        }

        // Recuperer l'erreur de connexion s'il y en a une
        $error = $authenticationUtils->getLastAuthenticationError();
        // Dernier identifiant entre par l'utilisateur
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/deconnexion', name: 'app_logout')]
    public function logout(): void
    {
        // Ce controleur peut etre vide - il sera intercepte par la cle logout dans le firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator,
        UserRepository $userRepository,
        RoleRepository $roleRepository,
    ): Response {
        // Si l'utilisateur est deja connecte, rediriger vers l'espace utilisateur
        if ($this->getUser()) {
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('inscription', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_register');
            }

            $pseudo = trim($request->request->get('pseudo', ''));
            $email = trim($request->request->get('email', ''));
            $password = $request->request->get('password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            // Validation du mot de passe (CNIL : 12 caracteres minimum, majuscule, minuscule, chiffre, special)
            if ($password !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            $passwordErrors = $this->validatePasswordCnil($password);
            $errors = array_merge($errors, $passwordErrors);

            // Verifier si l'email existe deja
            if ($userRepository->findByEmail($email)) {
                $errors[] = 'Un compte existe déjà avec cet email.';
            }

            // Verifier si le pseudo existe deja
            if ($userRepository->findByPseudo($pseudo)) {
                $errors[] = 'Ce pseudo est déjà utilisé.';
            }

            if (empty($errors)) {
                // Recuperer le role 'utilisateur' depuis la base
                $role = $roleRepository->findOneBy(['libelle' => 'utilisateur']);
                if (!$role) {
                    $this->addFlash('error', 'Erreur de configuration : le rôle utilisateur est introuvable.');
                    return $this->redirectToRoute('app_register');
                }

                $user = new User();
                $user->setPseudo($pseudo);
                $user->setEmail($email);
                $user->setPassword($passwordHasher->hashPassword($user, $password));
                $user->setRole($role);

                // Validation de l'entite
                $violations = $validator->validate($user);
                if (count($violations) > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = $violation->getMessage();
                    }
                }

                // Si toujours pas d'erreurs apres validation, creer l'utilisateur
                if (empty($errors)) {
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.');
                    return $this->redirectToRoute('app_login');
                }
            }

            // Afficher les erreurs
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('app_register');
        }

        return $this->render('security/register.html.twig');
    }

    #[Route('/mot-de-passe-oublie', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        MailService $mailService,
    ): Response {
        // Si l'utilisateur est deja connecte, rediriger
        if ($this->getUser()) {
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('mot-de-passe-oublie', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $email = trim($request->request->get('email', ''));
            $pseudo = trim($request->request->get('pseudo', ''));

            // Verifier si la combinaison email + pseudo existe
            $user = $userRepository->findByEmail($email);

            if ($user && $user->getPseudo() === $pseudo) {
                // Generer un mot de passe temporaire securise (12+ caracteres, conforme CNIL)
                $temporaryPassword = $this->generateSecurePassword();

                // Hasher et enregistrer le nouveau mot de passe
                $user->setPassword($passwordHasher->hashPassword($user, $temporaryPassword));
                $user->setMotDePasseAModifier(true);

                $entityManager->flush();

                // Envoyer le mot de passe temporaire par email
                try {
                    $mailService->sendTemporaryPassword($email, $pseudo, $temporaryPassword);
                } catch (\Exception $e) {
                    // On continue meme si l'envoi echoue pour ne pas reveler l'existence du compte
                }
            }

            // Toujours afficher le meme message pour des raisons de securite
            $this->addFlash('success', 'Si un compte correspond à ces informations, un email contenant un mot de passe temporaire vous a été envoyé.');
            return $this->redirectToRoute('app_forgot_password');
        }

        return $this->render('security/forgot_password.html.twig');
    }

    #[Route('/changement-mot-de-passe', name: 'app_change_password', methods: ['GET', 'POST'])]
    public function changePassword(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        // Accessible uniquement quand connecte ET motdepasse_a_modifier = true
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!$user->isMotDePasseAModifier()) {
            $this->addFlash('info', 'Vous n\'avez pas besoin de changer votre mot de passe.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('changement-mot-de-passe', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_change_password');
            }

            $newPassword = $request->request->get('new_password', '');
            $confirmPassword = $request->request->get('confirm_password', '');

            $errors = [];

            if ($newPassword !== $confirmPassword) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }

            $passwordErrors = $this->validatePasswordCnil($newPassword);
            $errors = array_merge($errors, $passwordErrors);

            if (empty($errors)) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
                $user->setMotDePasseAModifier(false);

                $entityManager->flush();

                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
                return $this->redirectBasedOnRole($user);
            }

            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }

            return $this->redirectToRoute('app_change_password');
        }

        return $this->render('security/change_password.html.twig');
    }

    /**
     * Valide un mot de passe selon les regles CNIL :
     * - 12 caracteres minimum
     * - Au moins une majuscule
     * - Au moins une minuscule
     * - Au moins un chiffre
     * - Au moins un caractere special
     *
     * @return string[] Liste des erreurs de validation
     */
    private function validatePasswordCnil(string $password): array
    {
        $errors = [];

        if (strlen($password) < 12) {
            $errors[] = 'Le mot de passe doit contenir au moins 12 caractères (recommandation CNIL).';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une lettre majuscule.';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une lettre minuscule.';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre.';
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un caractère spécial.';
        }

        return $errors;
    }

    /**
     * Genere un mot de passe securise conforme CNIL (12+ caracteres).
     * Contient majuscules, minuscules, chiffres et caracteres speciaux.
     */
    private function generateSecurePassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        // Garantir au moins un caractere de chaque type
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Remplir le reste avec un melange aleatoire
        $allChars = $uppercase . $lowercase . $digits . $special;
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Melanger le mot de passe pour que les positions ne soient pas previsibles
        return str_shuffle($password);
    }

    /**
     * Redirige l'utilisateur en fonction de son role.
     */
    private function redirectBasedOnRole(User $user): Response
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true)) {
            return $this->redirectToRoute('app_espace_admin');
        }

        if (in_array('ROLE_EMPLOYEE', $roles, true)) {
            return $this->redirectToRoute('app_espace_employe');
        }

        return $this->redirectToRoute('app_espace_utilisateur');
    }
}

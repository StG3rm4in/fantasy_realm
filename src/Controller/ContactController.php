<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\MailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, UserRepository $userRepository, MailService $mailService): Response
    {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('contact-form', $token)) {
                $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                return $this->redirectToRoute('app_contact');
            }

            $email = trim($request->request->get('email', ''));
            $pseudo = trim($request->request->get('pseudo', ''));
            $message = trim($request->request->get('message', ''));

            $errors = [];
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email invalide';
            }
            if (empty($pseudo) || strlen($pseudo) < 1) {
                $errors[] = 'Le pseudo est requis';
            }
            if (empty($message) || strlen($message) < 10) {
                $errors[] = 'Le message doit contenir au moins 10 caractères';
            }

            // Validation côté serveur : vérifier que le pseudo existe en BDD (message générique anti-énumération)
            if (!empty($pseudo) && !$userRepository->findByPseudo($pseudo)) {
                $errors[] = 'Les informations saisies sont incorrectes.';
            }

            if (empty($errors)) {
                try {
                    $mailService->sendContactForm($email, $pseudo, $message);
                    $this->addFlash('success', 'Votre message a bien été envoyé ! Nous vous répondrons dans les plus brefs délais.');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer plus tard.');
                }
            } else {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig');
    }
}

<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Commentaire;
use App\Entity\Personnage;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\CommentaireRepository;
use App\Repository\PersonnageRepository;
use App\Repository\UserRepository;
use App\Service\MailService;
use App\Service\MongoLogService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace-employe')]
#[IsGranted('ROLE_EMPLOYEE')]
class EspaceEmployeController extends AbstractController
{
    public function __construct(
        private MongoLogService $mongoLogService,
        private MailService $mailService,
    ) {
    }

    // =========================================================================
    // DASHBOARD
    // =========================================================================

    #[Route('', name: 'app_espace_employe')]
    public function index(
        PersonnageRepository $personnageRepository,
        CommentaireRepository $commentaireRepository,
        UserRepository $userRepository,
        ArticleRepository $articleRepository,
    ): Response {
        // --- Onglet Personnages ---
        // Personnages en attente : tous ceux avec autorise IS NULL (privés et partagés)
        $pendingPersonnages = $personnageRepository->createQueryBuilder('p')
            ->where('p.autorise IS NULL')
            ->orderBy('p.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();

        // Personnages récemment approuvés (autorise = true)
        $approvedPersonnages = $personnageRepository->createQueryBuilder('p')
            ->where('p.autorise = :autorise')
            ->setParameter('autorise', true)
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Personnages récemment rejetés (autorise = false)
        $rejectedPersonnages = $personnageRepository->createQueryBuilder('p')
            ->where('p.autorise = :autorise')
            ->setParameter('autorise', false)
            ->orderBy('p.dateCreation', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // --- Onglet Avis ---
        $pendingAvis = $commentaireRepository->findBy(
            ['statut' => 'en_attente'],
            ['dateCommentaire' => 'DESC']
        );

        $approvedAvis = $commentaireRepository->findBy(
            ['statut' => 'approuve'],
            ['dateCommentaire' => 'DESC'],
            10
        );

        // --- Onglet Utilisateurs ---
        // Ne charger que les utilisateurs avec le rôle 'utilisateur'
        $allUsers = $userRepository->findAll();
        $simpleUsers = array_filter($allUsers, function (User $user): bool {
            $roles = $user->getRoles();
            return !in_array('ROLE_EMPLOYEE', $roles) && !in_array('ROLE_ADMIN', $roles);
        });

        $activeUsers = array_filter($simpleUsers, fn(User $user): bool => !$user->isSuspendu());
        $suspendedUsers = array_filter($simpleUsers, fn(User $user): bool => $user->isSuspendu());

        // --- Onglet Articles ---
        $totalArticles = $articleRepository->count([]);
        $activeArticles = $articleRepository->count(['actif' => true]);

        return $this->render('espace_employe/index.html.twig', [
            // Personnages
            'pendingPersonnages' => $pendingPersonnages,
            'approvedPersonnages' => $approvedPersonnages,
            'rejectedPersonnages' => $rejectedPersonnages,
            'pendingCount' => count($pendingPersonnages),
            // Avis
            'pendingAvis' => $pendingAvis,
            'approvedAvis' => $approvedAvis,
            'pendingAvisCount' => count($pendingAvis),
            // Utilisateurs
            'activeUsers' => $activeUsers,
            'suspendedUsers' => $suspendedUsers,
            // Articles
            'totalArticles' => $totalArticles,
            'activeArticles' => $activeArticles,
        ]);
    }

    // =========================================================================
    // PERSONNAGES - Modération
    // =========================================================================

    #[Route('/personnages/{id}', name: 'app_employe_personnage_show', requirements: ['id' => '\d+'])]
    public function showPersonnage(Personnage $personnage): Response
    {
        // Page ouverte pour tous les personnages : l'employé doit pouvoir
        // supprimer un personnage déjà approuvé. Les boutons Approuver et
        // Rejeter ne s'affichent que pour un personnage en attente (template).
        return $this->render('espace_employe/personnage_show.html.twig', [
            'personnage' => $personnage,
        ]);
    }

    #[Route('/personnages/{id}/approuver', name: 'app_employe_personnage_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approvePersonnage(
        Personnage $personnage,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('approve-personnage-' . $personnage->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Un employé ne peut pas approuver son propre personnage
        if ($personnage->getUtilisateur() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modérer votre propre personnage.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Seul un personnage en attente peut être approuvé ou rejeté. Masquer les
        // boutons dans le template ne suffit pas : le serveur doit vérifier.
        if ($personnage->isAutorise() !== null) {
            $this->addFlash('error', 'Ce personnage a déjà été modéré.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Approuver le personnage
        $personnage->setAutorise(true);
        $em->flush();

        // Envoyer l'email de notification au propriétaire
        $owner = $personnage->getUtilisateur();
        if ($owner !== null) {
            // Un échec d'envoi (quota, SMTP indisponible) ne doit pas annuler l'action
            try {
                $this->mailService->sendPersonnageApproved(
                    $owner->getEmail(),
                    $owner->getPseudo(),
                    $personnage->getNom()
                );
            } catch (\Throwable $e) {
                $this->addFlash('warning', 'Action effectuée, mais le mail au propriétaire n\'a pas pu être envoyé.');
            }
        }

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            $personnage->getId(),
            $personnage->getNom(),
            'moderation_approbation',
            sprintf('Personnage "%s" approuvé par %s', $personnage->getNom(), $currentUser->getPseudo())
        );

        $this->addFlash('success', sprintf(
            'Le personnage "%s" a été approuvé et est maintenant visible dans la galerie.',
            $personnage->getNom()
        ));

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/personnages/{id}/rejeter', name: 'app_employe_personnage_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rejectPersonnage(
        Personnage $personnage,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject-personnage-' . $personnage->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Un employé ne peut pas rejeter son propre personnage
        if ($personnage->getUtilisateur() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modérer votre propre personnage.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Seul un personnage en attente peut être approuvé ou rejeté. Masquer les
        // boutons dans le template ne suffit pas : le serveur doit vérifier.
        if ($personnage->isAutorise() !== null) {
            $this->addFlash('error', 'Ce personnage a déjà été modéré.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // La raison est obligatoire
        $reason = trim((string) $request->request->get('reason', ''));
        if ($reason === '') {
            $this->addFlash('error', 'Le motif de rejet est obligatoire.');
            return $this->redirectToRoute('app_employe_personnage_show', ['id' => $personnage->getId()]);
        }

        $nomPersonnage = $personnage->getNom();
        $owner = $personnage->getUtilisateur();

        // Envoyer l'email de rejet au propriétaire AVANT la suppression
        if ($owner !== null) {
            // Un échec d'envoi (quota, SMTP indisponible) ne doit pas annuler l'action
            try {
                $this->mailService->sendPersonnageRejected(
                    $owner->getEmail(),
                    $owner->getPseudo(),
                    $nomPersonnage,
                    $reason
                );
            } catch (\Throwable $e) {
                $this->addFlash('warning', 'Action effectuée, mais le mail au propriétaire n\'a pas pu être envoyé.');
            }
        }

        // Log dans MongoDB avant suppression
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            $personnage->getId(),
            $nomPersonnage,
            'moderation_rejet',
            sprintf('Personnage "%s" rejeté et supprimé par %s. Motif : %s', $nomPersonnage, $currentUser->getPseudo(), $reason)
        );

        // Supprimer le personnage définitivement
        $em->remove($personnage);
        $em->flush();

        $this->addFlash('success', sprintf(
            'Le personnage "%s" a été rejeté et supprimé. Motif : %s',
            $nomPersonnage,
            $reason
        ));

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/personnages/{id}/supprimer', name: 'app_employe_personnage_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deletePersonnage(
        Personnage $personnage,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-personnage-' . $personnage->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $nomPersonnage = $personnage->getNom();

        // Log dans MongoDB avant suppression
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            $personnage->getId(),
            $nomPersonnage,
            'moderation_suppression',
            sprintf('Personnage "%s" supprimé par %s', $nomPersonnage, $currentUser->getPseudo())
        );

        $em->remove($personnage);
        $em->flush();

        $this->addFlash('success', sprintf('Le personnage "%s" a été supprimé.', $nomPersonnage));

        return $this->redirectToRoute('app_espace_employe');
    }

    // =========================================================================
    // AVIS - Modération
    // =========================================================================

    #[Route('/avis/{id}/approuver', name: 'app_employe_avis_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approveAvis(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('approve-avis-' . $commentaire->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Un employé ne modère pas son propre avis (comme pour les personnages)
        if ($commentaire->getAuteur() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modérer votre propre avis.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Seul un avis en attente se modère (évite un second mail au propriétaire)
        if ($commentaire->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cet avis a déjà été modéré.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $commentaire->setStatut('approuve');
        $em->flush();

        // Envoyer un email au propriétaire du personnage
        $personnage = $commentaire->getPersonnage();
        if ($personnage !== null) {
            $owner = $personnage->getUtilisateur();
            if ($owner !== null) {
                // Un échec d'envoi (quota, SMTP indisponible) ne doit pas annuler l'action
                try {
                    $this->mailService->sendCommentaireApproved(
                        $owner->getEmail(),
                        $owner->getPseudo(),
                        $personnage->getNom()
                    );
                } catch (\Throwable $e) {
                    $this->addFlash('warning', 'Action effectuée, mais le mail au propriétaire n\'a pas pu être envoyé.');
                }
            }
        }

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $personnageId = $personnage?->getId() ?? 0;
        $personnageNom = $personnage?->getNom() ?? 'inconnu';
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            $personnageId,
            $personnageNom,
            'moderation_avis_approuve',
            sprintf('Avis #%d approuvé sur le personnage "%s" par %s', $commentaire->getId(), $personnageNom, $currentUser->getPseudo())
        );

        $this->addFlash('success', 'L\'avis a été approuvé et est maintenant visible.');

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/avis/{id}/refuser', name: 'app_employe_avis_reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function rejectAvis(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject-avis-' . $commentaire->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Un employé ne modère pas son propre avis (comme pour les personnages)
        if ($commentaire->getAuteur() === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas modérer votre propre avis.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Seul un avis en attente se modère (évite un second mail au propriétaire)
        if ($commentaire->getStatut() !== 'en_attente') {
            $this->addFlash('error', 'Cet avis a déjà été modéré.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $commentaire->setStatut('refuse');
        $em->flush();

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $personnage = $commentaire->getPersonnage();
        $personnageId = $personnage?->getId() ?? 0;
        $personnageNom = $personnage?->getNom() ?? 'inconnu';
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            $personnageId,
            $personnageNom,
            'moderation_avis_refuse',
            sprintf('Avis #%d refusé sur le personnage "%s" par %s', $commentaire->getId(), $personnageNom, $currentUser->getPseudo())
        );

        $this->addFlash('success', 'L\'avis a été refusé.');

        return $this->redirectToRoute('app_espace_employe');
    }

    // =========================================================================
    // ARTICLES - CRUD
    // =========================================================================

    #[Route('/articles', name: 'app_employe_articles')]
    public function listArticles(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy([], ['nom' => 'ASC']);

        return $this->render('espace_employe/articles/index.html.twig', [
            'articles' => $articles,
        ]);
    }

    #[Route('/articles/nouveau', name: 'app_employe_article_create')]
    public function createArticle(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('article-create', $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_employe_articles');
            }

            $nom = trim((string) $request->request->get('nom', ''));
            $type = (string) $request->request->get('type', '');
            $allowedTypes = ['vetement', 'armure', 'accessoire', 'arme'];

            // Validation
            if ($nom === '' || mb_strlen($nom) > 50) {
                $this->addFlash('error', 'Le nom de l\'article est requis et ne doit pas dépasser 50 caractères.');
                return $this->render('espace_employe/articles/form.html.twig', [
                    'article' => null,
                    'isEdit' => false,
                    'nom' => $nom,
                    'type' => $type,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            if (!in_array($type, $allowedTypes, true)) {
                $this->addFlash('error', 'Le type d\'article est invalide.');
                return $this->render('espace_employe/articles/form.html.twig', [
                    'article' => null,
                    'isEdit' => false,
                    'nom' => $nom,
                    'type' => $type,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $article = new Article();
            $article->setNom($nom);
            $article->setType($type);
            $article->setActif(true);

            // Gérer l'upload de l'image en BLOB
            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('image');
            if ($imageFile !== null && $imageFile->isValid()) {
                $imageContent = file_get_contents($imageFile->getPathname());
                $article->setImage($imageContent);
            }

            $em->persist($article);
            $em->flush();

            // Log dans MongoDB
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $this->mongoLogService->log(
                $currentUser->getId(),
                $currentUser->getPseudo(),
                0,
                '',
                'article_creation',
                sprintf('Article "%s" (type: %s) créé par %s', $nom, $type, $currentUser->getPseudo())
            );

            $this->addFlash('success', sprintf('L\'article "%s" a été créé avec succès.', $nom));

            return $this->redirectToRoute('app_employe_articles');
        }

        return $this->render('espace_employe/articles/form.html.twig', [
            'article' => null,
            'isEdit' => false,
            'nom' => '',
            'type' => '',
        ]);
    }

    #[Route('/articles/{id}/modifier', name: 'app_employe_article_edit', requirements: ['id' => '\d+'])]
    public function editArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        if ($request->isMethod('POST')) {
            $token = $request->request->get('_token');
            if (!$this->isCsrfTokenValid('article-edit-' . $article->getId(), $token)) {
                $this->addFlash('error', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_employe_articles');
            }

            $nom = trim((string) $request->request->get('nom', ''));
            $type = (string) $request->request->get('type', '');
            $allowedTypes = ['vetement', 'armure', 'accessoire', 'arme'];

            // Validation
            if ($nom === '' || mb_strlen($nom) > 50) {
                $this->addFlash('error', 'Le nom de l\'article est requis et ne doit pas dépasser 50 caractères.');
                return $this->render('espace_employe/articles/form.html.twig', [
                    'article' => $article,
                    'isEdit' => true,
                    'nom' => $nom,
                    'type' => $type,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            if (!in_array($type, $allowedTypes, true)) {
                $this->addFlash('error', 'Le type d\'article est invalide.');
                return $this->render('espace_employe/articles/form.html.twig', [
                    'article' => $article,
                    'isEdit' => true,
                    'nom' => $nom,
                    'type' => $type,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $article->setNom($nom);
            $article->setType($type);

            // Gérer l'upload de la nouvelle image (optionnel en édition)
            /** @var UploadedFile|null $imageFile */
            $imageFile = $request->files->get('image');
            if ($imageFile !== null && $imageFile->isValid()) {
                $imageContent = file_get_contents($imageFile->getPathname());
                $article->setImage($imageContent);
            }

            $em->flush();

            // Log dans MongoDB
            /** @var User $currentUser */
            $currentUser = $this->getUser();
            $this->mongoLogService->log(
                $currentUser->getId(),
                $currentUser->getPseudo(),
                0,
                '',
                'article_modification',
                sprintf('Article "%s" (type: %s) modifié par %s', $nom, $type, $currentUser->getPseudo())
            );

            $this->addFlash('success', sprintf('L\'article "%s" a été modifié avec succès.', $nom));

            return $this->redirectToRoute('app_employe_articles');
        }

        return $this->render('espace_employe/articles/form.html.twig', [
            'article' => $article,
            'isEdit' => true,
            'nom' => $article->getNom(),
            'type' => $article->getType(),
        ]);
    }

    #[Route('/articles/{id}/desactiver', name: 'app_employe_article_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('toggle-article-' . $article->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_employe_articles');
        }

        $article->setActif(!$article->isActif());
        $em->flush();

        $status = $article->isActif() ? 'activé' : 'désactivé';

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'article_toggle',
            sprintf('Article "%s" %s par %s', $article->getNom(), $status, $currentUser->getPseudo())
        );

        $this->addFlash('success', sprintf('L\'article "%s" a été %s.', $article->getNom(), $status));

        return $this->redirectToRoute('app_employe_articles');
    }

    #[Route('/articles/{id}/supprimer', name: 'app_employe_article_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteArticle(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-article-' . $article->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_employe_articles');
        }

        // Vérifier si des personnages utilisent cet article
        if (!$article->getPersonnages()->isEmpty()) {
            $this->addFlash('error', sprintf(
                'Impossible de supprimer l\'article "%s" car il est utilisé par %d personnage(s). Vous pouvez le désactiver à la place.',
                $article->getNom(),
                $article->getPersonnages()->count()
            ));
            return $this->redirectToRoute('app_employe_articles');
        }

        $nomArticle = $article->getNom();

        // Log dans MongoDB avant suppression
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'article_suppression',
            sprintf('Article "%s" supprimé par %s', $nomArticle, $currentUser->getPseudo())
        );

        $em->remove($article);
        $em->flush();

        $this->addFlash('success', sprintf('L\'article "%s" a été supprimé.', $nomArticle));

        return $this->redirectToRoute('app_employe_articles');
    }

    // =========================================================================
    // UTILISATEURS - Gestion
    // =========================================================================

    #[Route('/utilisateurs/{id}/suspendre', name: 'app_employe_user_suspend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function suspendUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('suspend' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Vérifier qu'on ne suspend pas un administrateur ou un employé
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de suspendre cet utilisateur.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Vérifier qu'on ne se suspend pas soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous suspendre vous-même.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $user->setSuspendu(true);
        $em->flush();

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'utilisateur_suspension',
            sprintf('Utilisateur "%s" suspendu par %s', $user->getPseudo(), $currentUser->getPseudo())
        );

        $this->addFlash('success', sprintf('L\'utilisateur %s a été suspendu.', $user->getPseudo()));

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/utilisateurs/{id}/reactiver', name: 'app_employe_user_unsuspend', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unsuspendUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('unsuspend' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $user->setSuspendu(false);
        $em->flush();

        // Log dans MongoDB
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'utilisateur_reactivation',
            sprintf('Utilisateur "%s" réactivé par %s', $user->getPseudo(), $currentUser->getPseudo())
        );

        $this->addFlash('success', sprintf('L\'utilisateur %s a été réactivé.', $user->getPseudo()));

        return $this->redirectToRoute('app_espace_employe');
    }

    #[Route('/utilisateurs/{id}/supprimer', name: 'app_employe_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteUser(
        User $user,
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        // Vérifier le token CSRF
        if (!$this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Vérifier qu'on ne supprime pas un administrateur ou un employé
        if (in_array('ROLE_ADMIN', $user->getRoles()) || in_array('ROLE_EMPLOYEE', $user->getRoles())) {
            $this->addFlash('error', 'Impossible de supprimer cet utilisateur.');
            return $this->redirectToRoute('app_espace_employe');
        }

        // Vérifier qu'on ne se supprime pas soi-même
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas vous supprimer vous-même.');
            return $this->redirectToRoute('app_espace_employe');
        }

        $pseudo = $user->getPseudo();
        $userId = $user->getId();

        // Log dans MongoDB avant suppression
        /** @var User $currentUser */
        $currentUser = $this->getUser();
        $this->mongoLogService->log(
            $currentUser->getId(),
            $currentUser->getPseudo(),
            0,
            '',
            'utilisateur_suppression',
            sprintf('Utilisateur "%s" (id: %d) supprimé par %s', $pseudo, $userId, $currentUser->getPseudo())
        );

        // RGPD : son pseudo est retiré du journal d'activité (y compris du log ci-dessus)
        $this->mongoLogService->anonymiserUtilisateur($userId, $pseudo);

        // Supprimer l'utilisateur et toutes ses données (cascade via entity config)
        $em->remove($user);
        $em->flush();

        $this->addFlash('success', sprintf('L\'utilisateur %s a été supprimé définitivement.', $pseudo));

        return $this->redirectToRoute('app_espace_employe');
    }
}

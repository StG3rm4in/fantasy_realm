<?php

namespace App\Controller;

use App\Entity\Personnage;
use App\Entity\User;
use App\Form\PersonnageType;
use App\Repository\ArticleRepository;
use App\Repository\PersonnageRepository;
use App\Service\MongoLogService;
use App\Service\PersonnageImageGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/espace-utilisateur')]
#[IsGranted('ROLE_USER')]
class EspaceUtilisateurController extends AbstractController
{
    public function __construct(
        private MongoLogService $mongoLogService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_espace_utilisateur')]
    public function index(PersonnageRepository $personnageRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier si le mot de passe doit etre change
        if ($user->isMotDePasseAModifier()) {
            return $this->redirectToRoute('app_change_password');
        }

        $personnages = $personnageRepository->findByUser($user->getId());

        return $this->render('espace_utilisateur/index.html.twig', [
            'personnages' => $personnages,
        ]);
    }

    #[Route('/personnage/nouveau', name: 'app_personnage_create', methods: ['GET', 'POST'])]
    public function createPersonnage(
        Request $request,
        PersonnageImageGenerator $imageGenerator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $personnage = new Personnage();
        $form = $this->createForm(PersonnageType::class, $personnage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $personnage->setUtilisateur($user);
            $personnage->setAutorise(null); // En attente de moderation
            $personnage->setDateCreation(new \DateTimeImmutable());

            // Image depuis le canvas client-side
            $imageBase64 = $request->request->get('character_image_data');
            if ($imageBase64) {
                $base64 = preg_replace('#^data:image/\w+;base64,#', '', $imageBase64);
                $binaryData = base64_decode($base64);
                if ($binaryData !== false) {
                    $personnage->setImage($binaryData);
                }
            }

            $this->entityManager->persist($personnage);

            try {
                $this->entityManager->flush();
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $this->addFlash('error', 'Un personnage avec ce nom existe déjà. Veuillez choisir un autre nom.');
                return $this->render('espace_utilisateur/personnage_create.html.twig', [
                    'form' => $form,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            // Fallback : generation serveur si pas d'image client
            if (!$imageBase64) {
                try {
                    $imagePath = $imageGenerator->generateFromPersonnage($personnage);
                    $imageData = file_get_contents($this->getParameter('kernel.project_dir') . '/public' . $imagePath);
                    if ($imageData !== false) {
                        $personnage->setImage($imageData);
                        $this->entityManager->flush();
                    }
                } catch (\Exception $e) {
                    // L'image n'est pas critique, on continue
                }
            }

            // Log MongoDB
            $this->mongoLogService->logCreation(
                $user->getId(),
                $user->getPseudo(),
                $personnage->getId(),
                $personnage->getNom(),
            );

            if ($personnage->isPartage()) {
                $this->addFlash('info', 'Votre personnage a été créé. Comme il est partagé publiquement, il sera visible après validation par un modérateur.');
            } else {
                $this->addFlash('success', 'Votre personnage a été créé avec succès !');
            }

            return $this->redirectToRoute('app_espace_utilisateur');
        }

        return $this->render('espace_utilisateur/personnage_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/personnage/{id}/modifier', name: 'app_personnage_edit', methods: ['GET', 'POST'])]
    public function editPersonnage(
        Personnage $personnage,
        Request $request,
        PersonnageImageGenerator $imageGenerator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier que l'utilisateur est le proprietaire
        if ($personnage->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le propriétaire de ce personnage.');
        }

        // Verifier que le personnage est approuve
        if ($personnage->isAutorise() !== true) {
            $this->addFlash('error', 'Vous ne pouvez modifier que les personnages approuvés.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        $form = $this->createForm(PersonnageType::class, $personnage);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Image depuis le canvas client-side
            $imageBase64 = $request->request->get('character_image_data');
            if ($imageBase64) {
                $base64 = preg_replace('#^data:image/\w+;base64,#', '', $imageBase64);
                $binaryData = base64_decode($base64);
                if ($binaryData !== false) {
                    $personnage->setImage($binaryData);
                }
            } else {
                // Fallback : generation serveur
                try {
                    $imagePath = $imageGenerator->generateFromPersonnage($personnage);
                    $imageData = file_get_contents($this->getParameter('kernel.project_dir') . '/public' . $imagePath);
                    if ($imageData !== false) {
                        $personnage->setImage($imageData);
                    }
                } catch (\Exception $e) {
                    // L'image n'est pas critique, on continue
                }
            }

            // Nom déjà pris par un autre personnage : message clair au lieu d'une erreur 500
            $homonyme = $this->entityManager->getRepository(Personnage::class)->findOneBy(['nom' => $personnage->getNom()]);
            if ($homonyme !== null && $homonyme !== $personnage) {
                $this->addFlash('error', 'Un personnage avec ce nom existe déjà. Veuillez choisir un autre nom.');
                return $this->render('espace_utilisateur/personnage_edit.html.twig', [
                    'form' => $form,
                    'personnage' => $personnage,
                ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            // Si le personnage est partage, remettre en attente de moderation
            if ($personnage->isPartage()) {
                $personnage->setAutorise(null);
                $this->addFlash('info', 'Votre personnage a été modifié. Comme il est partagé, il sera à nouveau soumis à la modération.');
            } else {
                $this->addFlash('success', 'Votre personnage a été modifié avec succès !');
            }

            $this->entityManager->flush();

            // Log MongoDB
            $this->mongoLogService->logModification(
                $user->getId(),
                $user->getPseudo(),
                $personnage->getId(),
                $personnage->getNom(),
            );

            return $this->redirectToRoute('app_espace_utilisateur');
        }

        return $this->render('espace_utilisateur/personnage_edit.html.twig', [
            'form' => $form,
            'personnage' => $personnage,
        ]);
    }

    #[Route('/personnage/{id}/accessoires', name: 'app_personnage_accessoires', methods: ['GET', 'POST'])]
    public function manageAccessoires(
        Personnage $personnage,
        Request $request,
        ArticleRepository $articleRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier que l'utilisateur est le proprietaire
        if ($personnage->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le propriétaire de ce personnage.');
        }

        // Verifier que le personnage est approuve
        if ($personnage->isAutorise() !== true) {
            $this->addFlash('error', 'Vous ne pouvez gérer les accessoires que des personnages approuvés.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        // Gerer l'ajout/retrait d'un article via POST
        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');
            $articleId = $request->request->getInt('article_id');

            if (!$this->isCsrfTokenValid('accessoires_' . $personnage->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_personnage_accessoires', ['id' => $personnage->getId()]);
            }

            $article = $articleRepository->find($articleId);

            if (!$article) {
                $this->addFlash('error', 'Article introuvable.');
                return $this->redirectToRoute('app_personnage_accessoires', ['id' => $personnage->getId()]);
            }

            if ($action === 'ajouter') {
                if (!$personnage->getArticles()->contains($article)) {
                    $personnage->addArticle($article);
                    $this->entityManager->flush();

                    // Log MongoDB
                    $this->mongoLogService->logAjoutEquipement(
                        $user->getId(),
                        $user->getPseudo(),
                        $personnage->getId(),
                        $personnage->getNom(),
                        $article->getNom(),
                    );

                    $this->addFlash('success', 'L\'article "' . $article->getNom() . '" a été ajouté à votre personnage.');
                } else {
                    $this->addFlash('info', 'Cet article est déjà équipé sur votre personnage.');
                }
            } elseif ($action === 'retirer') {
                if ($personnage->getArticles()->contains($article)) {
                    $personnage->removeArticle($article);
                    $this->entityManager->flush();

                    // Log MongoDB
                    $this->mongoLogService->logSuppressionEquipement(
                        $user->getId(),
                        $user->getPseudo(),
                        $personnage->getId(),
                        $personnage->getNom(),
                        $article->getNom(),
                    );

                    $this->addFlash('success', 'L\'article "' . $article->getNom() . '" a été retiré de votre personnage.');
                } else {
                    $this->addFlash('info', 'Cet article n\'est pas équipé sur votre personnage.');
                }
            }

            return $this->redirectToRoute('app_personnage_accessoires', ['id' => $personnage->getId()]);
        }

        // Recuperer les articles actifs groupes par type
        $activeArticles = $articleRepository->findActive();
        $articlesByType = [];
        foreach ($activeArticles as $article) {
            $type = $article->getType();
            $articlesByType[$type][] = $article;
        }

        // Articles actuellement equipes sur le personnage
        $equippedArticleIds = [];
        foreach ($personnage->getArticles() as $article) {
            $equippedArticleIds[] = $article->getId();
        }

        return $this->render('espace_utilisateur/personnage_accessoires.html.twig', [
            'personnage' => $personnage,
            'articlesByType' => $articlesByType,
            'equippedArticleIds' => $equippedArticleIds,
        ]);
    }

    #[Route('/personnage/{id}/partager', name: 'app_personnage_toggle_partage', methods: ['POST'])]
    public function togglePartage(Personnage $personnage, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier que l'utilisateur est le proprietaire
        if ($personnage->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le propriétaire de ce personnage.');
        }

        if (!$this->isCsrfTokenValid('partage_' . $personnage->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        $wasShared = $personnage->isPartage();
        $personnage->setPartage(!$wasShared);

        if (!$wasShared) {
            // Ne remettre en modération que si le personnage n'est pas déjà approuvé
            if ($personnage->isAutorise() !== true) {
                $personnage->setAutorise(null);
                $flashMessage = 'Votre personnage est maintenant partagé. Il sera visible dans la galerie après validation par un modérateur.';
            } else {
                $flashMessage = 'Votre personnage est maintenant partagé et visible dans la galerie communautaire.';
            }

            $this->mongoLogService->logPartage(
                $user->getId(),
                $user->getPseudo(),
                $personnage->getId(),
                $personnage->getNom(),
            );

            $this->addFlash('info', $flashMessage);
        } else {
            // On arrete le partage
            $this->mongoLogService->logArretPartage(
                $user->getId(),
                $user->getPseudo(),
                $personnage->getId(),
                $personnage->getNom(),
            );

            $this->addFlash('success', 'Votre personnage n\'est plus partagé publiquement.');
        }

        $this->entityManager->flush();

        return $this->redirectToRoute('app_espace_utilisateur');
    }

    #[Route('/personnage/{id}/dupliquer', name: 'app_personnage_dupliquer', methods: ['GET', 'POST'])]
    public function dupliquerPersonnage(
        Personnage $personnage,
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier que l'utilisateur est le proprietaire
        if ($personnage->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le propriétaire de ce personnage.');
        }

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('dupliquer_' . $personnage->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton CSRF invalide.');
                return $this->redirectToRoute('app_espace_utilisateur');
            }

            $newName = trim($request->request->get('nouveau_nom', ''));

            if (empty($newName)) {
                $this->addFlash('error', 'Le nom du nouveau personnage est requis.');
                return $this->redirectToRoute('app_personnage_dupliquer', ['id' => $personnage->getId()]);
            }

            // mb_strlen compte les caractères : strlen comptait les octets (« é » = 2)
            if (mb_strlen($newName) < 2 || mb_strlen($newName) > 50) {
                $this->addFlash('error', 'Le nom doit contenir entre 2 et 50 caractères.');
                return $this->redirectToRoute('app_personnage_dupliquer', ['id' => $personnage->getId()]);
            }

            // Nom déjà pris : message clair au lieu d'une erreur 500
            if ($this->entityManager->getRepository(Personnage::class)->findOneBy(['nom' => $newName]) !== null) {
                $this->addFlash('error', 'Un personnage avec ce nom existe déjà. Veuillez choisir un autre nom.');
                return $this->redirectToRoute('app_personnage_dupliquer', ['id' => $personnage->getId()]);
            }

            // Creer la copie
            $newPersonnage = new Personnage();
            $newPersonnage->setNom($newName);
            $newPersonnage->setGenre($personnage->getGenre());
            $newPersonnage->setCouleurPeau($personnage->getCouleurPeau());
            $newPersonnage->setCouleurYeux($personnage->getCouleurYeux());
            $newPersonnage->setCouleurCheveux($personnage->getCouleurCheveux());
            $newPersonnage->setFormeYeux($personnage->getFormeYeux());
            $newPersonnage->setFormeNez($personnage->getFormeNez());
            $newPersonnage->setFormeBouche($personnage->getFormeBouche());
            $newPersonnage->setPartage(false);
            $newPersonnage->setAutorise(null); // En attente de moderation
            $newPersonnage->setUtilisateur($user);
            $newPersonnage->setDateCreation(new \DateTimeImmutable());

            // Copier l'image si presente
            if ($personnage->getImage() !== null) {
                $imageData = $personnage->getImage();
                if (is_resource($imageData)) {
                    $imageData = stream_get_contents($imageData);
                    rewind($personnage->getImage());
                }
                $newPersonnage->setImage($imageData);
            }

            // Copier tous les articles (equipements)
            foreach ($personnage->getArticles() as $article) {
                $newPersonnage->addArticle($article);
            }

            $this->entityManager->persist($newPersonnage);
            $this->entityManager->flush();

            // Log MongoDB
            $this->mongoLogService->logDuplication(
                $user->getId(),
                $user->getPseudo(),
                $personnage->getId(),
                $personnage->getNom(),
                $newName,
            );

            $this->addFlash('success', 'Le personnage "' . $personnage->getNom() . '" a été dupliqué sous le nom "' . $newName . '".');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        return $this->render('espace_utilisateur/personnage_dupliquer.html.twig', [
            'personnage' => $personnage,
        ]);
    }

    #[Route('/personnage/{id}/supprimer', name: 'app_personnage_delete', methods: ['POST'])]
    public function deletePersonnage(Personnage $personnage, Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Verifier que l'utilisateur est le proprietaire
        if ($personnage->getUtilisateur() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le propriétaire de ce personnage.');
        }

        if (!$this->isCsrfTokenValid('delete_' . $personnage->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        $nomPersonnage = $personnage->getNom();
        $personnageId = $personnage->getId();

        // Log MongoDB avant la suppression
        $this->mongoLogService->logSuppression(
            $user->getId(),
            $user->getPseudo(),
            $personnageId,
            $nomPersonnage,
        );

        $this->entityManager->remove($personnage);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le personnage "' . $nomPersonnage . '" a été supprimé.');
        return $this->redirectToRoute('app_espace_utilisateur');
    }

    #[Route('/supprimer-compte', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // L'administrateur est un compte unique, qu'on ne peut pas recréer depuis
        // l'application : il ne peut pas supprimer son propre compte.
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Le compte administrateur ne peut pas être supprimé.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        if (!$this->isCsrfTokenValid('delete_account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_espace_utilisateur');
        }

        // Supprimer le compte (RGPD - droit a l'effacement)
        // Les personnages et commentaires seront supprimes en cascade (orphanRemoval + cascade remove)
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        // Invalider la session et deconnecter l'utilisateur
        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $this->addFlash('success', 'Votre compte a été supprimé conformément à votre droit à l\'effacement (RGPD). Nous sommes désolés de vous voir partir.');
        return $this->redirectToRoute('app_home');
    }
}

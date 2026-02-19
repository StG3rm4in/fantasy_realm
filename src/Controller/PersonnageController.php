<?php

namespace App\Controller;

use App\Entity\Commentaire;
use App\Entity\Personnage;
use App\Form\CommentaireType;
use App\Repository\PersonnageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller pour les pages publiques des personnages
 */
class PersonnageController extends AbstractController
{
    #[Route('/personnages', name: 'app_personnages', methods: ['GET'])]
    public function gallery(Request $request, PersonnageRepository $personnageRepository): Response
    {
        // Récupérer les filtres
        $genre = $request->query->get('genre');
        $dateDebut = $request->query->get('date_debut');
        $dateFin = $request->query->get('date_fin');
        $pseudo = $request->query->get('pseudo');

        // Construire la requête avec filtres
        $qb = $personnageRepository->createQueryBuilder('p')
            ->leftJoin('p.utilisateur', 'u')
            ->where('p.partage = :partage')
            ->andWhere('p.autorise = :autorise')
            ->setParameter('partage', true)
            ->setParameter('autorise', true)
            ->orderBy('p.dateCreation', 'DESC');

        if ($genre && $genre !== '') {
            $qb->andWhere('p.genre = :genre')
               ->setParameter('genre', $genre);
        }

        if ($dateDebut) {
            $qb->andWhere('p.dateCreation >= :dateDebut')
               ->setParameter('dateDebut', new \DateTime($dateDebut));
        }

        if ($dateFin) {
            $qb->andWhere('p.dateCreation <= :dateFin')
               ->setParameter('dateFin', new \DateTime($dateFin . ' 23:59:59'));
        }

        if ($pseudo) {
            $qb->andWhere('u.pseudo LIKE :pseudo')
               ->setParameter('pseudo', '%' . $pseudo . '%');
        }

        // Pagination
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        // Compter le total avant d'appliquer limit/offset
        $countQb = clone $qb;
        $countQb->select('COUNT(p.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $limit));

        // Appliquer la pagination
        $personnages = $qb
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();

        return $this->render('personnage/index.html.twig', [
            'personnages' => $personnages,
            'filters' => [
                'genre' => $genre,
                'date_debut' => $dateDebut,
                'date_fin' => $dateFin,
                'pseudo' => $pseudo,
            ],
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    #[Route('/personnage/{id}', name: 'app_personnage_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(Personnage $personnage, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier les permissions de visibilité
        $canView = ($personnage->isPartage() && $personnage->isAutorise() === true)
            || ($this->getUser() && $personnage->getUtilisateur() === $this->getUser())
            || $this->isGranted('ROLE_EMPLOYEE');

        if (!$canView) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce personnage.');
        }

        // Récupérer uniquement les commentaires approuvés
        $commentairesApprouves = [];
        foreach ($personnage->getCommentaires() as $commentaire) {
            if ($commentaire->isApprouve()) {
                $commentairesApprouves[] = $commentaire;
            }
        }

        // Vérifier si l'utilisateur a déjà un avis
        $userCommentaire = null;
        if ($this->getUser()) {
            foreach ($personnage->getCommentaires() as $commentaire) {
                if ($commentaire->getAuteur() === $this->getUser()) {
                    $userCommentaire = $commentaire;
                    break;
                }
            }
        }

        // Formulaire de commentaire
        $commentaireForm = null;

        if ($this->getUser() && $personnage->isPartage() && $personnage->isAutorise() === true && !$userCommentaire
            && $personnage->getUtilisateur() !== $this->getUser()) {
            $commentaire = new Commentaire();
            $commentaire->setPersonnage($personnage);
            $commentaire->setAuteur($this->getUser());

            $commentaireForm = $this->createForm(CommentaireType::class, $commentaire);
            $commentaireForm->handleRequest($request);

            if ($commentaireForm->isSubmitted() && $commentaireForm->isValid()) {
                $em->persist($commentaire);
                $em->flush();

                $this->addFlash('success', 'Votre avis a été enregistré ! Il sera visible après validation par notre équipe de modération.');
                return $this->redirectToRoute('app_personnage_show', ['id' => $personnage->getId()]);
            }
        }

        return $this->render('personnage/detail.html.twig', [
            'personnage' => $personnage,
            'commentaires' => $commentairesApprouves,
            'commentaireForm' => $commentaireForm?->createView(),
            'userCommentaire' => $userCommentaire,
        ]);
    }

    #[Route('/personnage/avis/{id}/modifier', name: 'app_avis_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function editAvis(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($commentaire->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas modifier cet avis.');
        }

        $form = $this->createForm(CommentaireType::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setStatut('en_attente');
            $em->flush();

            $this->addFlash('success', 'Votre avis a été modifié ! Il sera à nouveau examiné par notre équipe de modération.');
            return $this->redirectToRoute('app_personnage_show', ['id' => $commentaire->getPersonnage()->getId()]);
        }

        return $this->render('personnage/edit_avis.html.twig', [
            'form' => $form,
            'commentaire' => $commentaire,
            'personnage' => $commentaire->getPersonnage(),
        ]);
    }

    #[Route('/personnage/avis/{id}/supprimer', name: 'app_avis_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteAvis(
        Commentaire $commentaire,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if ($commentaire->getAuteur() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous ne pouvez pas supprimer cet avis.');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete-avis-' . $commentaire->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_personnage_show', ['id' => $commentaire->getPersonnage()->getId()]);
        }

        $personnageId = $commentaire->getPersonnage()->getId();
        $em->remove($commentaire);
        $em->flush();

        $this->addFlash('success', 'Votre avis a été supprimé.');
        return $this->redirectToRoute('app_personnage_show', ['id' => $personnageId]);
    }
}

<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LegalController extends AbstractController
{
    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('pages/mentions_legales.html.twig');
    }

    #[Route('/conditions-generales-de-vente', name: 'app_cgv')]
    public function cgv(): Response
    {
        return $this->render('pages/cgv.html.twig');
    }

    #[Route('/politique-de-confidentialite', name: 'app_politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('pages/politique_confidentialite.html.twig');
    }
}

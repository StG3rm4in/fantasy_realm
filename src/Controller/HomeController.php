<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $features = [
            [
                'icon' => 'bi-lightning-charge-fill',
                'title' => 'Création avancée',
                'description' => 'Personnalisez chaque aspect de votre héros: apparence, équipement, accessoires.',
            ],
            [
                'icon' => 'bi-shield-fill',
                'title' => 'Catalogue riche',
                'description' => 'Accédez à des centaines d\'accessoires pour rendre votre personnage unique.',
            ],
            [
                'icon' => 'bi-people-fill',
                'title' => 'Communauté active',
                'description' => 'Partagez vos créations et découvrez celles des autres joueurs.',
            ],
            [
                'icon' => 'bi-star-fill',
                'title' => 'Avis & Notes',
                'description' => 'Évaluez les personnages partagés et recevez des retours sur vos créations.',
            ],
        ];

        return $this->render('home/index.html.twig', [
            'features' => $features,
        ]);
    }
}

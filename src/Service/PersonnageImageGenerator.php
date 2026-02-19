<?php

namespace App\Service;

use App\Entity\Personnage;

class PersonnageImageGenerator
{
    private string $uploadsDir;
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->uploadsDir = $projectDir . '/public/uploads/personnages/';

        if (!is_dir($this->uploadsDir)) {
            mkdir($this->uploadsDir, 0755, true);
        }
    }

    /**
     * Sauvegarde une image depuis les données base64 envoyées par le client
     */
    public function saveFromBase64(string $base64Data, Personnage $personnage): string
    {
        // Retirer le préfixe "data:image/png;base64," si présent
        if (strpos($base64Data, ',') !== false) {
            $imageData = explode(',', $base64Data);
            $decodedImage = base64_decode($imageData[1]);
        } else {
            $decodedImage = base64_decode($base64Data);
        }

        $filename = sprintf(
            'personnage_%s_%s.png',
            $personnage->getId() ?? uniqid(),
            time()
        );

        file_put_contents($this->uploadsDir . $filename, $decodedImage);

        return '/uploads/personnages/' . $filename;
    }

    /**
     * Génère une image de personnage côté serveur (fallback)
     * Utilise GD pour superposer des calques
     */
    public function generateFromPersonnage(Personnage $personnage): string
    {
        $width = 400;
        $height = 600;

        // Créer une image avec fond transparent
        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefill($image, 0, 0, $transparent);

        // Dessiner un placeholder simple pour l'instant
        $this->drawPlaceholder($image, $personnage, $width, $height);

        // Sauvegarder l'image
        $filename = sprintf('personnage_%d_%d.png', $personnage->getId(), time());
        $outputPath = $this->uploadsDir . $filename;
        imagepng($image, $outputPath);
        imagedestroy($image);

        return '/uploads/personnages/' . $filename;
    }

    /**
     * Dessine un placeholder représentant le personnage
     */
    private function drawPlaceholder($image, Personnage $personnage, int $width, int $height): void
    {
        // Couleurs basées sur les caractéristiques du personnage
        $skinColors = [
            'pale' => [255, 220, 200],
            'claire' => [240, 200, 180],
            'beige' => [220, 180, 150],
            'olive' => [190, 160, 120],
            'bronze' => [160, 120, 80],
            'brune' => [140, 100, 70],
            'foncee' => [100, 70, 50],
            'ebene' => [60, 40, 30],
        ];

        $hairColors = [
            'noir' => [20, 20, 20],
            'brun-fonce' => [50, 30, 20],
            'brun' => [80, 50, 30],
            'chatain' => [120, 80, 50],
            'blond-fonce' => [160, 120, 60],
            'blond' => [200, 170, 100],
            'blond-platine' => [230, 220, 190],
            'roux' => [180, 70, 30],
            'auburn' => [140, 50, 20],
            'gris' => [150, 150, 150],
            'blanc' => [240, 240, 240],
        ];

        $eyeColors = [
            'marron' => [100, 70, 40],
            'bleu' => [70, 130, 180],
            'vert' => [60, 140, 80],
            'noisette' => [120, 100, 60],
            'gris' => [140, 140, 140],
            'ambre' => [180, 120, 40],
            'violet' => [140, 80, 180],
        ];

        $skinColor = $skinColors[$personnage->getCouleurPeau()] ?? [200, 180, 160];
        $hairColor = $hairColors[$personnage->getCouleurCheveux()] ?? [80, 50, 30];
        $eyeColor = $eyeColors[$personnage->getCouleurYeux()] ?? [100, 70, 40];

        // Dessiner la tête (cercle)
        $headColor = imagecolorallocate($image, $skinColor[0], $skinColor[1], $skinColor[2]);
        imagefilledellipse($image, $width / 2, $height / 3, 150, 180, $headColor);

        // Dessiner les cheveux
        $hairColorGd = imagecolorallocate($image, $hairColor[0], $hairColor[1], $hairColor[2]);
        imagefilledellipse($image, $width / 2, $height / 3 - 40, 160, 100, $hairColorGd);

        // Dessiner les yeux
        $eyeColorGd = imagecolorallocate($image, $eyeColor[0], $eyeColor[1], $eyeColor[2]);
        imagefilledellipse($image, $width / 2 - 30, $height / 3, 20, 15, $eyeColorGd);
        imagefilledellipse($image, $width / 2 + 30, $height / 3, 20, 15, $eyeColorGd);

        // Dessiner le corps
        $bodyColor = imagecolorallocate($image, 100, 100, 120);
        imagefilledrectangle($image, $width / 2 - 80, $height / 2, $width / 2 + 80, $height - 50, $bodyColor);

        // Ajouter le nom du personnage
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $font = 5;
        $text = $personnage->getNom();
        $textWidth = imagefontwidth($font) * strlen($text);
        imagestring($image, $font, ($width - $textWidth) / 2, $height - 30, $text, $textColor);
    }

    /**
     * Supprime l'ancienne image d'un personnage
     */
    public function deleteImage(?string $imagePath): void
    {
        if ($imagePath && file_exists($this->projectDir . '/public' . $imagePath)) {
            unlink($this->projectDir . '/public' . $imagePath);
        }
    }
}


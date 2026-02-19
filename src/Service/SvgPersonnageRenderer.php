<?php

namespace App\Service;

use App\Entity\Personnage;

/**
 * Port PHP fidèle de assets/services/PersonnageRenderer.js.
 * Génère un SVG string (viewBox 0 0 200 260) pour un Personnage donné.
 * Stocké directement en BLOB dans la colonne `image` (détecté par getImageDataUrl).
 */
class SvgPersonnageRenderer
{
    private const SKIN_COLORS = [
        'pale'   => '#F5E0D0',
        'claire' => '#FDDCB5',
        'beige'  => '#E8C99A',
        'olive'  => '#D4A574',
        'bronze' => '#C68642',
        'brune'  => '#A0724B',
        'foncee' => '#8D5524',
        'ebene'  => '#5C3A1E',
    ];

    private const HAIR_COLORS = [
        'noir'          => '#1A1A1A',
        'brun-fonce'    => '#3D2B1F',
        'brun'          => '#6B4226',
        'chatain'       => '#8B6042',
        'blond-fonce'   => '#C8A050',
        'blond'         => '#F4D03F',
        'blond-platine' => '#F0E6C8',
        'roux'          => '#D35400',
        'auburn'        => '#A0402A',
        'gris'          => '#9B9B9B',
        'blanc'         => '#ECF0F1',
    ];

    private const EYE_COLORS = [
        'marron'   => '#8B4513',
        'bleu'     => '#4A90D9',
        'vert'     => '#2ECC71',
        'noisette' => '#A0722A',
        'gris'     => '#7F8C8D',
        'ambre'    => '#D4A017',
        'violet'   => '#9B59B6',
    ];

    public function generate(Personnage $personnage): string
    {
        $skinColor  = self::SKIN_COLORS[$personnage->getCouleurPeau()]    ?? self::SKIN_COLORS['claire'];
        $hairColor  = self::HAIR_COLORS[$personnage->getCouleurCheveux()] ?? self::HAIR_COLORS['brun'];
        $eyeColor   = self::EYE_COLORS[$personnage->getCouleurYeux()]     ?? self::EYE_COLORS['marron'];
        $skinShadow = $this->darkenColor($skinColor, 20);

        $genre = $personnage->getGenre() ?? 'Masculin';
        if (in_array($genre, ['Feminin', 'feminin'], true)) {
            $genre = 'Féminin';
        }

        $s  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" width="400" height="520">';

        // ── Defs ──────────────────────────────────────────────────────────────
        $s .= '<defs>';
        $s .= '<radialGradient id="bgG" cx="50%" cy="45%" r="60%">';
        $s .= '<stop offset="0%"   stop-color="#1e2d4a"/>';
        $s .= '<stop offset="60%"  stop-color="#111827"/>';
        $s .= '<stop offset="100%" stop-color="#060a10"/>';
        $s .= '</radialGradient>';
        $s .= '<linearGradient id="bodyG" x1="0" y1="0" x2="0" y2="1">';
        $s .= '<stop offset="0%"   stop-color="#2e3d65"/>';
        $s .= '<stop offset="50%"  stop-color="#232e50"/>';
        $s .= '<stop offset="100%" stop-color="#1a2238"/>';
        $s .= '</linearGradient>';
        $s .= '</defs>';

        // ── 1. Fond ───────────────────────────────────────────────────────────
        $s .= '<rect width="200" height="260" fill="url(#bgG)"/>';

        // ── 2+3. Cou + Corps ─────────────────────────────────────────────────
        $s .= $this->svgBody($genre, $skinColor, $skinShadow);

        // ── 4. Visage ─────────────────────────────────────────────────────────
        $s .= $this->svgFace($genre, $skinColor, $skinShadow);

        // ── 5. Oreilles ───────────────────────────────────────────────────────
        $s .= $this->svgEars($skinColor, $skinShadow);

        // ── 6. Yeux ───────────────────────────────────────────────────────────
        $s .= $this->svgEyes($personnage->getFormeYeux() ?? 'amande', $eyeColor, $genre);

        // ── 7. Sourcils ───────────────────────────────────────────────────────
        $s .= $this->svgEyebrows($genre, $hairColor);

        // ── 8. Nez ────────────────────────────────────────────────────────────
        $s .= $this->svgNose($personnage->getFormeNez() ?? 'droit', $skinShadow);

        // ── 9. Bouche ─────────────────────────────────────────────────────────
        $s .= $this->svgMouth($personnage->getFormeBouche() ?? 'moyenne', $genre);

        // ── 10. Cheveux (dernier — cy≈70, yeux cy=100 : pas d'overlap) ───────
        $s .= $this->svgHair($genre, $hairColor);

        // ── 11. Nom ───────────────────────────────────────────────────────────
        $s .= $this->svgName($personnage->getNom() ?? '');

        $s .= '</svg>';

        return $s;
    }

    // =========================================================================
    // Utilitaires
    // =========================================================================

    private function darkenColor(string $hex, int $amount): string
    {
        $hex = ltrim($hex, '#');
        $r   = max(0, hexdec(substr($hex, 0, 2)) - $amount);
        $g   = max(0, hexdec(substr($hex, 2, 2)) - $amount);
        $b   = max(0, hexdec(substr($hex, 4, 2)) - $amount);
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private function esc(string $str): string
    {
        return htmlspecialchars($str, ENT_XML1, 'UTF-8');
    }

    // =========================================================================
    // Générateurs SVG — miroir exact de PersonnageRenderer.js
    // =========================================================================

    private function svgFace(string $genre, string $skinColor, string $skinShadow): string
    {
        $s = '';
        if ($genre === 'Féminin') {
            $s .= '<ellipse cx="100" cy="105" rx="40" ry="48" fill="' . $skinColor . '"/>';
            $s .= '<ellipse cx="100" cy="125" rx="30" ry="15" fill="' . $skinShadow . '" opacity="0.1"/>';
        } elseif ($genre === 'Masculin') {
            $s .= '<rect x="62" y="60" width="76" height="90" rx="32" fill="' . $skinColor . '"/>';
            $s .= '<rect x="68" y="120" width="64" height="20" rx="10" fill="' . $skinShadow . '" opacity="0.15"/>';
        } else {
            $s .= '<ellipse cx="100" cy="105" rx="38" ry="46" fill="' . $skinColor . '"/>';
            $s .= '<ellipse cx="100" cy="123" rx="28" ry="14" fill="' . $skinShadow . '" opacity="0.12"/>';
        }
        return $s;
    }

    private function svgEars(string $skinColor, string $skinShadow): string
    {
        $s  = '<ellipse cx="58"  cy="105" rx="8" ry="12" fill="' . $skinColor . '"/>';
        $s .= '<ellipse cx="58"  cy="105" rx="5" ry="8"  fill="' . $skinShadow . '" opacity="0.3"/>';
        $s .= '<ellipse cx="142" cy="105" rx="8" ry="12" fill="' . $skinColor . '"/>';
        $s .= '<ellipse cx="142" cy="105" rx="5" ry="8"  fill="' . $skinShadow . '" opacity="0.3"/>';
        return $s;
    }

    private function svgEyes(string $formeYeux, string $eyeColor, string $genre): string
    {
        $s = '';

        // Œil gauche
        switch ($formeYeux) {
            case 'ronds':
                $s .= '<ellipse cx="82" cy="100" rx="8" ry="8" fill="white"/>';
                break;
            case 'brides':
                $s .= '<ellipse cx="82" cy="100" rx="12" ry="6" fill="white"/>';
                break;
            case 'tombants':
                $s .= '<path d="M71 103 Q82 91 93 97 Q82 108 71 103Z" fill="white"/>';
                break;
            case 'amande-allongee':
                $s .= '<path d="M68 100 Q82 89 96 100 Q82 109 68 100Z" fill="white"/>';
                break;
            case 'amande':
            default:
                $s .= '<path d="M71 100 Q82 90 93 100 Q82 108 71 100Z" fill="white"/>';
                break;
        }
        $s .= '<circle cx="82" cy="100" r="5.5" fill="' . $eyeColor . '"/>';
        $s .= '<circle cx="82" cy="100" r="2.8" fill="#111"/>';
        $s .= '<circle cx="84" cy="98"  r="1.5" fill="white" opacity="0.85"/>';

        // Œil droit (miroir : cx = 200 − 82 = 118)
        switch ($formeYeux) {
            case 'ronds':
                $s .= '<ellipse cx="118" cy="100" rx="8" ry="8" fill="white"/>';
                break;
            case 'brides':
                $s .= '<ellipse cx="118" cy="100" rx="12" ry="6" fill="white"/>';
                break;
            case 'tombants':
                $s .= '<path d="M107 97 Q118 91 129 103 Q118 108 107 97Z" fill="white"/>';
                break;
            case 'amande-allongee':
                $s .= '<path d="M104 100 Q118 89 132 100 Q118 109 104 100Z" fill="white"/>';
                break;
            case 'amande':
            default:
                $s .= '<path d="M107 100 Q118 90 129 100 Q118 108 107 100Z" fill="white"/>';
                break;
        }
        $s .= '<circle cx="118" cy="100" r="5.5" fill="' . $eyeColor . '"/>';
        $s .= '<circle cx="118" cy="100" r="2.8" fill="#111"/>';
        $s .= '<circle cx="120" cy="98"  r="1.5" fill="white" opacity="0.85"/>';

        // Cils féminins
        if ($genre === 'Féminin') {
            $s .= '<line x1="70" y1="96" x2="67" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="74" y1="94" x2="72" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="90" y1="94" x2="92" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="94" y1="96" x2="97" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="106" y1="96" x2="103" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="110" y1="94" x2="108" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="126" y1="94" x2="128" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
            $s .= '<line x1="130" y1="96" x2="133" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        }

        return $s;
    }

    private function svgEyebrows(string $genre, string $hairColor): string
    {
        $sw  = ($genre === 'Masculin') ? '3' : '2';
        $s   = '<path d="M72 88 Q82 83 92 87" stroke="' . $hairColor . '" stroke-width="' . $sw . '" fill="none" stroke-linecap="round"/>';
        $s  .= '<path d="M108 87 Q118 83 128 88" stroke="' . $hairColor . '" stroke-width="' . $sw . '" fill="none" stroke-linecap="round"/>';
        return $s;
    }

    private function svgNose(string $formeNez, string $skinShadow): string
    {
        $s = '';
        switch ($formeNez) {
            case 'aquilin':
                $s .= '<path d="M100 98 Q103 108 102 114 Q100 120 98 116" stroke="' . $skinShadow . '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                $s .= '<path d="M94 118 Q100 122 106 118" stroke="' . $skinShadow . '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                break;
            case 'retrousse':
                $s .= '<path d="M100 100 Q100 112 97 115 Q100 120 103 115 Q100 112 100 100" stroke="' . $skinShadow . '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                $s .= '<circle cx="95" cy="117" r="2" fill="' . $skinShadow . '" opacity="0.4"/>';
                $s .= '<circle cx="105" cy="117" r="2" fill="' . $skinShadow . '" opacity="0.4"/>';
                break;
            case 'large':
                $s .= '<line x1="100" y1="100" x2="100" y2="116" stroke="' . $skinShadow . '" stroke-width="2" stroke-linecap="round"/>';
                $s .= '<path d="M92 118 Q100 124 108 118" stroke="' . $skinShadow . '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                break;
            case 'fin':
                $s .= '<line x1="100" y1="100" x2="100" y2="118" stroke="' . $skinShadow . '" stroke-width="1.5" stroke-linecap="round"/>';
                $s .= '<path d="M97 118 Q100 121 103 118" stroke="' . $skinShadow . '" stroke-width="1" fill="none" stroke-linecap="round"/>';
                break;
            case 'plat':
                $s .= '<line x1="100" y1="105" x2="100" y2="116" stroke="' . $skinShadow . '" stroke-width="2" stroke-linecap="round"/>';
                $s .= '<path d="M90 118 Q100 125 110 118" stroke="' . $skinShadow . '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                break;
            case 'droit':
            default:
                $s .= '<line x1="100" y1="100" x2="100" y2="118" stroke="' . $skinShadow . '" stroke-width="2" stroke-linecap="round"/>';
                $s .= '<path d="M95 118 Q100 122 105 118" stroke="' . $skinShadow . '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
                break;
        }
        return $s;
    }

    private function svgMouth(string $formeBouche, string $genre): string
    {
        $s    = '';
        $lipD = ($genre === 'Féminin') ? '#c0392b' : '#a0665a';
        $lipL = ($genre === 'Féminin') ? '#e74c3c' : '#b8776b';

        switch ($formeBouche) {
            case 'fine':
                $s .= '<path d="M88 132 Q100 137 112 132" stroke="' . $lipD . '" stroke-width="2" fill="none" stroke-linecap="round"/>';
                break;
            case 'pulpeuse':
                $s .= '<path d="M85 130 Q100 126 115 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipL . '" opacity="0.7" stroke-linecap="round"/>';
                $s .= '<path d="M85 130 Q100 142 115 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipD . '" opacity="0.6" stroke-linecap="round"/>';
                break;
            case 'large':
                $s .= '<path d="M85 130 Q100 127 115 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipL . '" opacity="0.6" stroke-linecap="round"/>';
                $s .= '<path d="M85 130 Q100 138 115 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipD . '" opacity="0.5" stroke-linecap="round"/>';
                break;
            case 'petite':
                $s .= '<path d="M92 130 Q100 127 108 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipL . '" opacity="0.6" stroke-linecap="round"/>';
                $s .= '<path d="M92 130 Q100 137 108 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipD . '" opacity="0.5" stroke-linecap="round"/>';
                break;
            case 'moyenne':
            default:
                $s .= '<path d="M87 130 Q100 128 113 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipL . '" opacity="0.6" stroke-linecap="round"/>';
                $s .= '<path d="M87 130 Q100 138 113 130" stroke="' . $lipD . '" stroke-width="1.5" fill="' . $lipD . '" opacity="0.5" stroke-linecap="round"/>';
                break;
        }
        return $s;
    }

    private function svgHair(string $genre, string $hairColor): string
    {
        $s        = '';
        $hairDark = $this->darkenColor($hairColor, 25);

        if ($genre === 'Féminin') {
            $s .= '<ellipse cx="100" cy="72" rx="48" ry="22" fill="' . $hairColor . '"/>';
            $s .= '<rect x="54" y="70" width="18" height="70" rx="9" fill="' . $hairColor . '"/>';
            $s .= '<rect x="128" y="70" width="18" height="70" rx="9" fill="' . $hairColor . '"/>';
            $s .= '<ellipse cx="100" cy="62" rx="44" ry="16" fill="' . $hairDark . '" opacity="0.4"/>';
            $s .= '<path d="M62 78 Q75 58 100 62 Q125 58 138 78" fill="' . $hairColor . '"/>';
            $s .= '<path d="M68 78 Q80 65 100 68 Q120 65 132 78" fill="' . $hairDark . '" opacity="0.3"/>';
        } elseif ($genre === 'Masculin') {
            $s .= '<ellipse cx="100" cy="70" rx="44" ry="20" fill="' . $hairColor . '"/>';
            $s .= '<path d="M60 80 Q65 50 100 48 Q135 50 140 80" fill="' . $hairColor . '"/>';
            $s .= '<path d="M65 78 Q70 55 100 53 Q130 55 135 78" fill="' . $hairDark . '" opacity="0.3"/>';
            $s .= '<rect x="56" y="72" width="12" height="25" rx="6" fill="' . $hairColor . '"/>';
            $s .= '<rect x="132" y="72" width="12" height="25" rx="6" fill="' . $hairColor . '"/>';
        } else {
            $s .= '<ellipse cx="100" cy="70" rx="46" ry="22" fill="' . $hairColor . '"/>';
            $s .= '<path d="M58 80 Q68 52 100 50 Q132 52 142 80" fill="' . $hairColor . '"/>';
            $s .= '<path d="M64 78 Q74 58 100 56 Q126 58 136 78" fill="' . $hairDark . '" opacity="0.3"/>';
            $s .= '<rect x="55" y="72" width="14" height="45" rx="7" fill="' . $hairColor . '"/>';
            $s .= '<rect x="131" y="72" width="14" height="45" rx="7" fill="' . $hairColor . '"/>';
        }
        return $s;
    }

    private function svgName(string $nom): string
    {
        if ($nom === '') {
            return '';
        }
        $s  = '<line x1="25" y1="218" x2="175" y2="218" stroke="rgba(139,92,246,0.5)" stroke-width="0.8"/>';
        $s .= '<text x="100" y="236" text-anchor="middle" font-family="Georgia, serif" font-size="11" font-weight="bold" fill="#e2d9f3" letter-spacing="0.5">' . $this->esc($nom) . '</text>';
        return $s;
    }

    private function svgBody(string $genre, string $skinColor, string $skinShadow): string
    {
        $s = '';

        if ($genre === 'Féminin') {
            $s .= '<path d="M90 150 L110 150 L113 168 L87 168 Z" fill="' . $skinColor . '"/>';
            $s .= '<path d="M103 150 L110 150 L113 168 L106 168 Z" fill="' . $skinShadow . '" opacity="0.18"/>';
            $s .= '<path d="M87 168 C74 170 54 177 40 192 L34 260 L166 260 L160 192 C146 177 126 170 113 168 Z" fill="url(#bodyG)"/>';
            $s .= '<path d="M87 168 C74 170 54 177 40 192 L34 260 L72 260 L72 172 Z" fill="rgba(0,0,0,0.11)"/>';
            $s .= '<path d="M113 168 C126 170 146 177 160 192 L166 260 L128 260 L128 172 Z" fill="rgba(255,255,255,0.04)"/>';
            $s .= '<path d="M87 168 L100 186 L113 168" stroke="rgba(255,255,255,0.14)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';

        } elseif ($genre === 'Masculin') {
            $s .= '<path d="M88 150 L112 150 L115 168 L85 168 Z" fill="' . $skinColor . '"/>';
            $s .= '<path d="M103 150 L112 150 L115 168 L106 168 Z" fill="' . $skinShadow . '" opacity="0.18"/>';
            $s .= '<path d="M85 168 C70 170 46 178 32 193 L25 260 L175 260 L168 193 C154 178 130 170 115 168 Z" fill="url(#bodyG)"/>';
            $s .= '<path d="M85 168 C70 170 46 178 32 193 L25 260 L68 260 L68 172 Z" fill="rgba(0,0,0,0.12)"/>';
            $s .= '<path d="M115 168 C130 170 154 178 168 193 L175 260 L132 260 L132 172 Z" fill="rgba(255,255,255,0.04)"/>';
            $s .= '<path d="M85 168 L100 190 L115 168" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';

        } else {
            $s .= '<path d="M89 150 L111 150 L114 168 L86 168 Z" fill="' . $skinColor . '"/>';
            $s .= '<path d="M103 150 L111 150 L114 168 L106 168 Z" fill="' . $skinShadow . '" opacity="0.18"/>';
            $s .= '<path d="M86 168 C72 170 50 177 36 192 L30 260 L170 260 L164 192 C150 177 128 170 114 168 Z" fill="url(#bodyG)"/>';
            $s .= '<path d="M86 168 C72 170 50 177 36 192 L30 260 L70 260 L70 172 Z" fill="rgba(0,0,0,0.11)"/>';
            $s .= '<path d="M114 168 C128 170 150 177 164 192 L170 260 L130 260 L130 172 Z" fill="rgba(255,255,255,0.04)"/>';
            $s .= '<path d="M86 168 L100 188 L114 168" stroke="rgba(255,255,255,0.14)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';
        }

        return $s;
    }
}

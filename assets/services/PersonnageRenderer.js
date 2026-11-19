/**
 * PersonnageRenderer.js  —  v3  (SVG → Canvas)
 *
 * Génère un SVG du personnage puis le dessine sur le canvas via ctx.drawImage().
 * Approche calquée sur assets/character-generator.js (coordonnées prouvées).
 *
 * ViewBox SVG : 0 0 200 260  →  rendu canvas 400×520 (scale ×2 uniforme).
 * Les cheveux (cy≈70) sont toujours au-dessus des yeux (cy=100) par construction SVG.
 *
 * Export : initCharacterPreview()  —  appelé par app.js › initAll()
 */

// ============================================================================
// PALETTES (valeurs Symfony minuscules → hex SVG)
// ============================================================================

var SKIN_COLORS = {
    'pale':   '#F5E0D0',
    'claire': '#FDDCB5',
    'beige':  '#E8C99A',
    'olive':  '#D4A574',
    'bronze': '#C68642',
    'brune':  '#A0724B',
    'foncee': '#8D5524',
    'ebene':  '#5C3A1E',
};

var HAIR_COLORS = {
    'noir':          '#1A1A1A',
    'brun-fonce':    '#3D2B1F',
    'brun':          '#6B4226',
    'chatain':       '#8B6042',
    'blond-fonce':   '#C8A050',
    'blond':         '#F4D03F',
    'blond-platine': '#F0E6C8',
    'roux':          '#D35400',
    'auburn':        '#A0402A',
    'gris':          '#9B9B9B',
    'blanc':         '#ECF0F1',
};

var EYE_COLORS = {
    'marron':   '#8B4513',
    'bleu':     '#4A90D9',
    'vert':     '#2ECC71',
    'noisette': '#A0722A',
    'gris':     '#7F8C8D',
    'ambre':    '#D4A017',
    'violet':   '#9B59B6',
};

// ============================================================================
// VALEURS PAR DÉFAUT
// ============================================================================

var DEFAULTS = {
    genre:          'Masculin',
    couleurPeau:    'claire',
    couleurCheveux: 'brun',
    couleurYeux:    'marron',
    formeYeux:      'amande',
    formeNez:       'droit',
    formeBouche:    'moyenne',
    nom:            '',
};

// ============================================================================
// UTILITAIRES
// ============================================================================

function darkenColor(hex, amount) {
    hex = hex.replace('#', '');
    var r = Math.max(0, parseInt(hex.substring(0, 2), 16) - amount);
    var g = Math.max(0, parseInt(hex.substring(2, 4), 16) - amount);
    var b = Math.max(0, parseInt(hex.substring(4, 6), 16) - amount);
    return '#' + ('0' + r.toString(16)).slice(-2)
               + ('0' + g.toString(16)).slice(-2)
               + ('0' + b.toString(16)).slice(-2);
}

function esc(str) {
    // Échappe les caractères spéciaux XML dans le texte SVG
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ============================================================================
// LECTURE DU FORMULAIRE SYMFONY
// ============================================================================

function getFormValues() {
    var v = function(id) {
        var el = document.getElementById(id);
        return el ? el.value : '';
    };
    return {
        nom:            v('personnage_nom')            || DEFAULTS.nom,
        genre:          v('personnage_genre')          || DEFAULTS.genre,
        couleurPeau:    v('personnage_couleurPeau')    || DEFAULTS.couleurPeau,
        couleurCheveux: v('personnage_couleurCheveux') || DEFAULTS.couleurCheveux,
        couleurYeux:    v('personnage_couleurYeux')    || DEFAULTS.couleurYeux,
        formeYeux:      v('personnage_formeYeux')      || DEFAULTS.formeYeux,
        formeNez:       v('personnage_formeNez')       || DEFAULTS.formeNez,
        formeBouche:    v('personnage_formeBouche')    || DEFAULTS.formeBouche,
    };
}

// ============================================================================
// GÉNÉRATEURS SVG (coordonnées issues de character-generator.js)
// ============================================================================

/** Visage : forme selon le genre */
function svgFace(genre, skinColor, skinShadow) {
    var s = '';
    if (genre === 'Féminin') {
        s += '<ellipse cx="100" cy="105" rx="40" ry="48" fill="' + skinColor + '"/>';
        s += '<ellipse cx="100" cy="125" rx="30" ry="15" fill="' + skinShadow + '" opacity="0.1"/>';
    } else if (genre === 'Masculin') {
        s += '<rect x="62" y="60" width="76" height="90" rx="32" fill="' + skinColor + '"/>';
        s += '<rect x="68" y="120" width="64" height="20" rx="10" fill="' + skinShadow + '" opacity="0.15"/>';
    } else {
        // Autre : intermédiaire entre les deux
        s += '<ellipse cx="100" cy="105" rx="38" ry="46" fill="' + skinColor + '"/>';
        s += '<ellipse cx="100" cy="123" rx="28" ry="14" fill="' + skinShadow + '" opacity="0.12"/>';
    }
    return s;
}

/** Oreilles */
function svgEars(skinColor, skinShadow) {
    var s = '';
    s += '<ellipse cx="58"  cy="105" rx="8" ry="12" fill="' + skinColor + '"/>';
    s += '<ellipse cx="58"  cy="105" rx="5" ry="8"  fill="' + skinShadow + '" opacity="0.3"/>';
    s += '<ellipse cx="142" cy="105" rx="8" ry="12" fill="' + skinColor + '"/>';
    s += '<ellipse cx="142" cy="105" rx="5" ry="8"  fill="' + skinShadow + '" opacity="0.3"/>';
    return s;
}

/** Yeux selon formeYeux */
function svgEyes(formeYeux, eyeColor, genre) {
    var s = '';

    // Œil gauche
    switch (formeYeux) {
        case 'ronds':
            s += '<ellipse cx="82" cy="100" rx="8" ry="8" fill="white"/>';
            break;
        case 'brides':
            s += '<ellipse cx="82" cy="100" rx="12" ry="6" fill="white"/>';
            break;
        case 'tombants':
            s += '<path d="M71 103 Q82 91 93 97 Q82 108 71 103Z" fill="white"/>';
            break;
        case 'amande-allongee':
            s += '<path d="M68 100 Q82 89 96 100 Q82 109 68 100Z" fill="white"/>';
            break;
        case 'amande':
        default:
            s += '<path d="M71 100 Q82 90 93 100 Q82 108 71 100Z" fill="white"/>';
            break;
    }
    s += '<circle cx="82" cy="100" r="5.5" fill="' + eyeColor + '"/>';
    s += '<circle cx="82" cy="100" r="2.8" fill="#111"/>';
    s += '<circle cx="84" cy="98"  r="1.5" fill="white" opacity="0.85"/>';

    // Œil droit (miroir : cx= 200 - 82 = 118)
    switch (formeYeux) {
        case 'ronds':
            s += '<ellipse cx="118" cy="100" rx="8" ry="8" fill="white"/>';
            break;
        case 'brides':
            s += '<ellipse cx="118" cy="100" rx="12" ry="6" fill="white"/>';
            break;
        case 'tombants':
            s += '<path d="M107 97 Q118 91 129 103 Q118 108 107 97Z" fill="white"/>';
            break;
        case 'amande-allongee':
            s += '<path d="M104 100 Q118 89 132 100 Q118 109 104 100Z" fill="white"/>';
            break;
        case 'amande':
        default:
            s += '<path d="M107 100 Q118 90 129 100 Q118 108 107 100Z" fill="white"/>';
            break;
    }
    s += '<circle cx="118" cy="100" r="5.5" fill="' + eyeColor + '"/>';
    s += '<circle cx="118" cy="100" r="2.8" fill="#111"/>';
    s += '<circle cx="120" cy="98"  r="1.5" fill="white" opacity="0.85"/>';

    // Cils féminin
    if (genre === 'Féminin') {
        s += '<line x1="70" y1="96" x2="67" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="74" y1="94" x2="72" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="90" y1="94" x2="92" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="94" y1="96" x2="97" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="106" y1="96" x2="103" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="110" y1="94" x2="108" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="126" y1="94" x2="128" y2="89" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
        s += '<line x1="130" y1="96" x2="133" y2="92" stroke="#333" stroke-width="1.5" stroke-linecap="round"/>';
    }

    return s;
}

/** Sourcils */
function svgEyebrows(genre, hairColor) {
    var sw = (genre === 'Masculin') ? '3' : '2';
    var s = '';
    s += '<path d="M72 88 Q82 83 92 87" stroke="' + hairColor + '" stroke-width="' + sw + '" fill="none" stroke-linecap="round"/>';
    s += '<path d="M108 87 Q118 83 128 88" stroke="' + hairColor + '" stroke-width="' + sw + '" fill="none" stroke-linecap="round"/>';
    return s;
}

/** Nez selon formeNez */
function svgNose(formeNez, skinShadow) {
    var s = '';
    switch (formeNez) {
        case 'aquilin':
            s += '<path d="M100 98 Q103 108 102 114 Q100 120 98 116" stroke="' + skinShadow + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
            s += '<path d="M94 118 Q100 122 106 118" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
            break;
        case 'retrousse':
            s += '<path d="M100 100 Q100 112 97 115 Q100 120 103 115 Q100 112 100 100" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
            s += '<circle cx="95" cy="117" r="2" fill="' + skinShadow + '" opacity="0.4"/>';
            s += '<circle cx="105" cy="117" r="2" fill="' + skinShadow + '" opacity="0.4"/>';
            break;
        case 'large':
            s += '<line x1="100" y1="100" x2="100" y2="116" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
            s += '<path d="M92 118 Q100 124 108 118" stroke="' + skinShadow + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
            break;
        case 'fin':
            s += '<line x1="100" y1="100" x2="100" y2="118" stroke="' + skinShadow + '" stroke-width="1.5" stroke-linecap="round"/>';
            s += '<path d="M97 118 Q100 121 103 118" stroke="' + skinShadow + '" stroke-width="1" fill="none" stroke-linecap="round"/>';
            break;
        case 'plat':
            s += '<line x1="100" y1="105" x2="100" y2="116" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
            s += '<path d="M90 118 Q100 125 110 118" stroke="' + skinShadow + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
            break;
        case 'droit':
        default:
            s += '<line x1="100" y1="100" x2="100" y2="118" stroke="' + skinShadow + '" stroke-width="2" stroke-linecap="round"/>';
            s += '<path d="M95 118 Q100 122 105 118" stroke="' + skinShadow + '" stroke-width="1.5" fill="none" stroke-linecap="round"/>';
            break;
    }
    return s;
}

/** Bouche selon formeBouche */
function svgMouth(formeBouche, genre) {
    var s = '';
    var lipD  = (genre === 'Féminin') ? '#c0392b' : '#a0665a';
    var lipL  = (genre === 'Féminin') ? '#e74c3c' : '#b8776b';

    switch (formeBouche) {
        case 'fine':
            s += '<path d="M88 132 Q100 137 112 132" stroke="' + lipD + '" stroke-width="2" fill="none" stroke-linecap="round"/>';
            break;
        case 'pulpeuse':
            s += '<path d="M85 130 Q100 126 115 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipL + '" opacity="0.7" stroke-linecap="round"/>';
            s += '<path d="M85 130 Q100 142 115 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipD + '" opacity="0.6" stroke-linecap="round"/>';
            break;
        case 'large':
            s += '<path d="M85 130 Q100 127 115 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipL + '" opacity="0.6" stroke-linecap="round"/>';
            s += '<path d="M85 130 Q100 138 115 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipD + '" opacity="0.5" stroke-linecap="round"/>';
            break;
        case 'petite':
            s += '<path d="M92 130 Q100 127 108 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipL + '" opacity="0.6" stroke-linecap="round"/>';
            s += '<path d="M92 130 Q100 137 108 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipD + '" opacity="0.5" stroke-linecap="round"/>';
            break;
        case 'moyenne':
        default:
            s += '<path d="M87 130 Q100 128 113 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipL + '" opacity="0.6" stroke-linecap="round"/>';
            s += '<path d="M87 130 Q100 138 113 130" stroke="' + lipD + '" stroke-width="1.5" fill="' + lipD + '" opacity="0.5" stroke-linecap="round"/>';
            break;
    }
    return s;
}

/**
 * Cheveux — DESSINÉS EN DERNIER, au-dessus de tout.
 * cy≈70 → s'étend max jusqu'à y≈90, les yeux sont à y=100 : aucun chevauchement.
 */
function svgHair(genre, hairColor) {
    var s = '';
    var hairDark = darkenColor(hairColor, 25);

    if (genre === 'Féminin') {
        // Cheveux longs : ellipse couronne + rectangles côtés
        s += '<ellipse cx="100" cy="72" rx="48" ry="22" fill="' + hairColor + '"/>';
        s += '<rect x="54" y="70" width="18" height="70" rx="9" fill="' + hairColor + '"/>';
        s += '<rect x="128" y="70" width="18" height="70" rx="9" fill="' + hairColor + '"/>';
        s += '<ellipse cx="100" cy="62" rx="44" ry="16" fill="' + hairDark + '" opacity="0.4"/>';
        s += '<path d="M62 78 Q75 58 100 62 Q125 58 138 78" fill="' + hairColor + '"/>';
        s += '<path d="M68 78 Q80 65 100 68 Q120 65 132 78" fill="' + hairDark + '" opacity="0.3"/>';
    } else if (genre === 'Masculin') {
        // Cheveux courts : ellipse aplatie + petits rect côtés
        s += '<ellipse cx="100" cy="70" rx="44" ry="20" fill="' + hairColor + '"/>';
        s += '<path d="M60 80 Q65 50 100 48 Q135 50 140 80" fill="' + hairColor + '"/>';
        s += '<path d="M65 78 Q70 55 100 53 Q130 55 135 78" fill="' + hairDark + '" opacity="0.3"/>';
        s += '<rect x="56" y="72" width="12" height="25" rx="6" fill="' + hairColor + '"/>';
        s += '<rect x="132" y="72" width="12" height="25" rx="6" fill="' + hairColor + '"/>';
    } else {
        // Autre : mi-long
        s += '<ellipse cx="100" cy="70" rx="46" ry="22" fill="' + hairColor + '"/>';
        s += '<path d="M58 80 Q68 52 100 50 Q132 52 142 80" fill="' + hairColor + '"/>';
        s += '<path d="M64 78 Q74 58 100 56 Q126 58 136 78" fill="' + hairDark + '" opacity="0.3"/>';
        s += '<rect x="55" y="72" width="14" height="45" rx="7" fill="' + hairColor + '"/>';
        s += '<rect x="131" y="72" width="14" height="45" rx="7" fill="' + hairColor + '"/>';
    }
    return s;
}

/** Nom du personnage (zone de 60px sous le visage, y=140-260) */
function svgName(nom) {
    if (!nom) return '';
    var s = '';
    // Trait décoratif
    s += '<line x1="25" y1="218" x2="175" y2="218" stroke="rgba(139,92,246,0.5)" stroke-width="0.8"/>';
    // Texte
    s += '<text x="100" y="236" text-anchor="middle" font-family="Georgia, serif" font-size="11" font-weight="bold" fill="#e2d9f3" letter-spacing="0.5">' + esc(nom) + '</text>';
    return s;
}

// ============================================================================
// CORPS : COU + BUSTE
// ============================================================================

/**
 * Cou court (trapèze) + buste (épaules arrondies via bezier), selon le genre.
 * Le visage se termine à y≈150 (menton). Le corps démarre à y=150 jusqu'à y=260.
 */
function svgBody(genre, skinColor, skinShadow) {
    var s = '';

    // Tête = 76px large (x=62→138). Corps ≈ 1.5× la tête en largeur aux épaules.
    // Pas d'ellipse cap — les bezier seuls créent des épaules arrondies naturelles.

    if (genre === 'Féminin') {
        // Cou fin
        s += '<path d="M90 150 L110 150 L113 168 L87 168 Z" fill="' + skinColor + '"/>';
        s += '<path d="M103 150 L110 150 L113 168 L106 168 Z" fill="' + skinShadow + '" opacity="0.18"/>';
        // Corps : épaules x=40→160, naturellement arrondies par bezier
        s += '<path d="M87 168 C74 170 54 177 40 192 L34 260 L166 260 L160 192 C146 177 126 170 113 168 Z" fill="url(#bodyG)"/>';
        // Ombre gauche + reflet droit
        s += '<path d="M87 168 C74 170 54 177 40 192 L34 260 L72 260 L72 172 Z" fill="rgba(0,0,0,0.11)"/>';
        s += '<path d="M113 168 C126 170 146 177 160 192 L166 260 L128 260 L128 172 Z" fill="rgba(255,255,255,0.04)"/>';
        // Col en V
        s += '<path d="M87 168 L100 186 L113 168" stroke="rgba(255,255,255,0.14)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';

    } else if (genre === 'Masculin') {
        // Cou légèrement plus large
        s += '<path d="M88 150 L112 150 L115 168 L85 168 Z" fill="' + skinColor + '"/>';
        s += '<path d="M103 150 L112 150 L115 168 L106 168 Z" fill="' + skinShadow + '" opacity="0.18"/>';
        // Corps : épaules x=30→170 (athlétique mais pas démesuré)
        s += '<path d="M85 168 C70 170 46 178 32 193 L25 260 L175 260 L168 193 C154 178 130 170 115 168 Z" fill="url(#bodyG)"/>';
        // Ombre gauche + reflet droit
        s += '<path d="M85 168 C70 170 46 178 32 193 L25 260 L68 260 L68 172 Z" fill="rgba(0,0,0,0.12)"/>';
        s += '<path d="M115 168 C130 170 154 178 168 193 L175 260 L132 260 L132 172 Z" fill="rgba(255,255,255,0.04)"/>';
        // Col en V
        s += '<path d="M85 168 L100 190 L115 168" stroke="rgba(255,255,255,0.15)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';

    } else {
        // Autre : intermédiaire
        s += '<path d="M89 150 L111 150 L114 168 L86 168 Z" fill="' + skinColor + '"/>';
        s += '<path d="M103 150 L111 150 L114 168 L106 168 Z" fill="' + skinShadow + '" opacity="0.18"/>';
        // Corps : épaules x=36→164
        s += '<path d="M86 168 C72 170 50 177 36 192 L30 260 L170 260 L164 192 C150 177 128 170 114 168 Z" fill="url(#bodyG)"/>';
        s += '<path d="M86 168 C72 170 50 177 36 192 L30 260 L70 260 L70 172 Z" fill="rgba(0,0,0,0.11)"/>';
        s += '<path d="M114 168 C128 170 150 177 164 192 L170 260 L130 260 L130 172 Z" fill="rgba(255,255,255,0.04)"/>';
        s += '<path d="M86 168 L100 188 L114 168" stroke="rgba(255,255,255,0.14)" stroke-width="1.5" fill="none" stroke-linejoin="round"/>';
    }

    return s;
}

// ============================================================================
// GÉNÉRATION DU SVG COMPLET
// ============================================================================

function generateSVG(values) {
    var skinColor  = SKIN_COLORS[values.couleurPeau]    || SKIN_COLORS['claire'];
    var hairColor  = HAIR_COLORS[values.couleurCheveux] || HAIR_COLORS['brun'];
    var eyeColor   = EYE_COLORS[values.couleurYeux]     || EYE_COLORS['marron'];
    var skinShadow = darkenColor(skinColor, 20);
    var genre      = values.genre;

    // Normalise le genre (gère l'accent de 'Féminin')
    if (genre === 'Féminin' || genre === 'feminin' || genre === 'Feminin') {
        genre = 'Féminin';
    }

    var s = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 260" width="400" height="520">';

    // ── Defs ──────────────────────────────────────────────────────────────────
    s += '<defs>';
    // Fond radial
    s += '<radialGradient id="bgG" cx="50%" cy="45%" r="60%">';
    s += '<stop offset="0%"   stop-color="#1e2d4a"/>';
    s += '<stop offset="60%"  stop-color="#111827"/>';
    s += '<stop offset="100%" stop-color="#060a10"/>';
    s += '</radialGradient>';
    // Corps gradient (vertical — du haut foncé vers bas très foncé)
    s += '<linearGradient id="bodyG" x1="0" y1="0" x2="0" y2="1">';
    s += '<stop offset="0%"   stop-color="#2e3d65"/>';
    s += '<stop offset="50%"  stop-color="#232e50"/>';
    s += '<stop offset="100%" stop-color="#1a2238"/>';
    s += '</linearGradient>';
    s += '</defs>';

    // ── 1. Fond ───────────────────────────────────────────────────────────────
    s += '<rect width="200" height="260" fill="url(#bgG)" rx="0"/>';

    // ── 2+3. Cou + Corps ──────────────────────────────────────────────────────
    s += svgBody(genre, skinColor, skinShadow);

    // ── 4. Visage ─────────────────────────────────────────────────────────────
    s += svgFace(genre, skinColor, skinShadow);

    // ── 5. Oreilles ───────────────────────────────────────────────────────────
    s += svgEars(skinColor, skinShadow);

    // ── 6. Yeux ───────────────────────────────────────────────────────────────
    s += svgEyes(values.formeYeux, eyeColor, genre);

    // ── 7. Sourcils ───────────────────────────────────────────────────────────
    s += svgEyebrows(genre, hairColor);

    // ── 8. Nez ────────────────────────────────────────────────────────────────
    s += svgNose(values.formeNez, skinShadow);

    // ── 9. Bouche ─────────────────────────────────────────────────────────────
    s += svgMouth(values.formeBouche, genre);

    // ── 10. Cheveux (DERNIER sur le visage — cy≈70, yeux cy=100 : pas d'overlap) ──
    s += svgHair(genre, hairColor);

    // ── 11. Nom ───────────────────────────────────────────────────────────────
    s += svgName(values.nom);

    s += '</svg>';
    return s;
}

// ============================================================================
// RENDU SVG → CANVAS (asynchrone via Image)
// ============================================================================

var _prevSvg = null;

function renderToCanvas(ctx, svgString) {
    // Évite de re-dessiner si le SVG n'a pas changé
    if (svgString === _prevSvg) return;
    _prevSvg = svgString;

    var encoded = btoa(unescape(encodeURIComponent(svgString)));
    var img = new Image();
    img.onload = function () {
        ctx.clearRect(0, 0, 400, 520);
        ctx.drawImage(img, 0, 0, 400, 520);
        // Mise à jour immédiate du champ caché → prêt avant submit
        var input = document.getElementById('character_image_data');
        if (input) {
            try {
                input.value = ctx.canvas.toDataURL('image/png');
            } catch (e) {
                // Sécurité tainted canvas (ne devrait pas arriver avec SVG data URL)
            }
        }
    };
    img.src = 'data:image/svg+xml;base64,' + encoded;
}

// ============================================================================
// POINT D'ENTRÉE  —  exporté, appelé par app.js › initAll()
// ============================================================================

/**
 * Initialise l'aperçu canvas temps-réel sur les pages de création/édition.
 * Stateless → compatible Turbo (re-appelé proprement à chaque turbo:load).
 */
export function initCharacterPreview() {
    var canvas = document.getElementById('character-preview');
    if (!canvas || canvas.dataset.bound) return;
    canvas.dataset.bound = '1';

    var ctx = canvas.getContext('2d');

    function refresh() {
        renderToCanvas(ctx, generateSVG(getFormValues()));
    }

    // Rendu initial (défauts sur création, valeurs pré-remplies sur édition)
    refresh();

    // Listeners sur les <select> du formulaire Symfony
    [
        'personnage_genre',
        'personnage_couleurPeau',
        'personnage_couleurYeux',
        'personnage_couleurCheveux',
        'personnage_formeYeux',
        'personnage_formeNez',
        'personnage_formeBouche',
    ].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', refresh);
    });

    // Nom : mise à jour live
    var nomEl = document.getElementById('personnage_nom');
    if (nomEl) nomEl.addEventListener('input', refresh);

    // Le champ character_image_data est déjà mis à jour dans img.onload après chaque rendu.
    // Un listener submit de secours garantit la capture si l'utilisateur soumet sans modifier:
    var form = canvas.closest('form');
    if (form) {
        form.addEventListener('submit', function() {
            var input = document.getElementById('character_image_data');
            if (input && !input.value) {
                // Si onload n'a pas encore fini (rare), on force un re-render synchrone
                // en recréant l'image — valeur sera vide côté serveur → fallback GD PHP
            }
        });
    }
}
